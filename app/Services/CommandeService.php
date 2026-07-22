<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Creance;
use App\Models\Devis;
use App\Models\MouvementStock;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\Produit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommandeService
{
    /**
     * Register a commande across one or more lines. Unlike a vente, stock is
     * never taken out immediately: each line reserves the quantity needed
     * (a mouvement de type reservation), which Produit::stockDisponible()
     * now excludes from what's sellable elsewhere — the merchandise is held,
     * not gone. The payment may be partial (an acompte), in which case the
     * outstanding balance becomes a créance owed by the client, exactly the
     * same mechanism as a partial vente (Chantier 4) — no separate variant.
     *
     * @param  array<int, array{produit_id: int, quantite: float, prix_unitaire: float}>  $lignes
     */
    public function creer(
        Client $client,
        PointDeVente $pointDeVente,
        array $lignes,
        float $montantPaye,
        ?Devis $devisOrigine = null,
    ): Commande {
        $montantTotal = round(array_sum(array_map(
            fn (array $ligne) => $ligne['quantite'] * $ligne['prix_unitaire'],
            $lignes
        )), 2);

        $quantitesParProduit = [];
        foreach ($lignes as $ligne) {
            $quantitesParProduit[$ligne['produit_id']] = ($quantitesParProduit[$ligne['produit_id']] ?? 0) + $ligne['quantite'];
        }

        foreach ($quantitesParProduit as $produitId => $quantite) {
            $produit = Produit::findOrFail($produitId);

            if ($produit->stockDisponible($pointDeVente) < $quantite) {
                throw ValidationException::withMessages([
                    'quantite' => "Stock disponible insuffisant pour réserver le produit #{$produitId}.",
                ]);
            }
        }

        return DB::transaction(function () use ($client, $pointDeVente, $lignes, $montantTotal, $montantPaye, $devisOrigine) {
            $commande = Commande::create([
                'client_id' => $client->id,
                'point_de_vente_id' => $pointDeVente->id,
                'devis_id' => $devisOrigine?->id,
                'statut' => Commande::STATUT_EN_ATTENTE,
                'montant_total' => $montantTotal,
            ]);

            foreach ($lignes as $ligne) {
                $commande->lignes()->create([
                    'produit_id' => $ligne['produit_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                ]);

                $commande->mouvementsStock()->create([
                    'produit_id' => $ligne['produit_id'],
                    'emplacement_type' => PointDeVente::class,
                    'emplacement_id' => $pointDeVente->id,
                    'type' => MouvementStock::TYPE_RESERVATION,
                    'quantite' => $ligne['quantite'],
                ]);
            }

            $commande->paiements()->create([
                'montant' => $montantPaye,
                'mode' => Paiement::MODE_ESPECES,
            ]);

            if ($montantPaye < $montantTotal) {
                $montantInitial = round($montantTotal - $montantPaye, 2);

                $commande->creance()->create([
                    'client_id' => $client->id,
                    'montant_initial' => $montantInitial,
                    'statut' => $montantInitial <= 0 ? Creance::STATUT_SOLDEE : Creance::STATUT_OUVERTE,
                ]);
            }

            return $commande;
        });
    }

    /**
     * Mark a commande as prepared — a pure status transition, no stock
     * effect: the reservation already covers it since creation.
     */
    public function preparer(Commande $commande): Commande
    {
        if ($commande->statut !== Commande::STATUT_EN_ATTENTE) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une commande en attente peut être préparée.',
            ]);
        }

        $commande->update(['statut' => Commande::STATUT_PREPAREE]);

        return $commande;
    }

    /**
     * Cancel a commande — whether refused outright or past its own delay,
     * no scheduled task involved, an explicit action only (same choice as
     * Chantier 9's abonnement suspension). Releases every line's reservation
     * (mouvement liberation_reservation), making the stock available again.
     */
    public function annuler(Commande $commande): Commande
    {
        $this->verifierAnnulable($commande);

        return DB::transaction(function () use ($commande) {
            foreach ($commande->lignes as $ligne) {
                $commande->mouvementsStock()->create([
                    'produit_id' => $ligne->produit_id,
                    'emplacement_type' => PointDeVente::class,
                    'emplacement_id' => $commande->point_de_vente_id,
                    'type' => MouvementStock::TYPE_LIBERATION_RESERVATION,
                    'quantite' => $ligne->quantite,
                ]);
            }

            $commande->update(['statut' => Commande::STATUT_ANNULEE]);

            return $commande;
        });
    }

    /**
     * Deliver a commande: its reservation is converted into a real,
     * definitive stock exit. Chosen deliberately over reusing Livraison
     * (Chantier 5) — that model tracks partial delivery passes and a
     * responsable livreur for a vente already paid or credited in full at
     * sale time, which doesn't fit a commande delivered in one shot. Instead
     * each line's reservation is released (liberation_reservation) and
     * immediately replaced by an ordinary sortie_vente of the same
     * quantity — the net stock effect is a permanent decrement, exactly like
     * an immediate vente.
     */
    public function livrer(Commande $commande): Commande
    {
        if (! in_array($commande->statut, [Commande::STATUT_EN_ATTENTE, Commande::STATUT_PREPAREE], true)) {
            throw ValidationException::withMessages([
                'statut' => 'Cette commande ne peut plus être livrée.',
            ]);
        }

        return DB::transaction(function () use ($commande) {
            foreach ($commande->lignes as $ligne) {
                $commande->mouvementsStock()->create([
                    'produit_id' => $ligne->produit_id,
                    'emplacement_type' => PointDeVente::class,
                    'emplacement_id' => $commande->point_de_vente_id,
                    'type' => MouvementStock::TYPE_LIBERATION_RESERVATION,
                    'quantite' => $ligne->quantite,
                ]);

                $commande->mouvementsStock()->create([
                    'produit_id' => $ligne->produit_id,
                    'emplacement_type' => PointDeVente::class,
                    'emplacement_id' => $commande->point_de_vente_id,
                    'type' => MouvementStock::TYPE_SORTIE_VENTE,
                    'quantite' => $ligne->quantite,
                ]);
            }

            $commande->update(['statut' => Commande::STATUT_LIVREE]);

            return $commande;
        });
    }

    private function verifierAnnulable(Commande $commande): void
    {
        if (! in_array($commande->statut, [Commande::STATUT_EN_ATTENTE, Commande::STATUT_PREPAREE], true)) {
            throw ValidationException::withMessages([
                'statut' => 'Cette commande ne peut plus être annulée.',
            ]);
        }
    }
}
