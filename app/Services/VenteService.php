<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Creance;
use App\Models\MouvementStock;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VenteService
{
    /**
     * Register a sale for a single product line. The payment may be partial
     * only when a client is supplied, in which case the outstanding balance
     * becomes a debt (créance) owed by that client. A full payment never
     * creates a debt, regardless of whether a client was supplied. Writes the
     * sale, its line, the resulting stock movement, the payment and (when
     * applicable) the debt atomically.
     *
     * @param  array{nom: string, telephone: ?string}|null  $client
     */
    public function enregistrerVente(
        User $vendeur,
        PointDeVente $pointDeVente,
        Produit $produit,
        float $quantite,
        float $montantPaye,
        ?array $client = null,
    ): Vente {
        $montantTotal = round((float) $produit->prix_vente * $quantite, 2);

        if ($montantPaye < $montantTotal && $client === null) {
            throw ValidationException::withMessages([
                'client' => 'Un client doit être renseigné pour un paiement partiel.',
            ]);
        }

        if ($produit->stockDisponible() < $quantite) {
            throw ValidationException::withMessages([
                'quantite' => 'Stock disponible insuffisant pour cette quantité.',
            ]);
        }

        return DB::transaction(function () use ($vendeur, $pointDeVente, $produit, $quantite, $montantTotal, $montantPaye, $client) {
            $vente = Vente::create([
                'point_de_vente_id' => $pointDeVente->id,
                'user_id' => $vendeur->id,
                'statut' => Vente::STATUT_VALIDEE,
                'montant_total' => $montantTotal,
            ]);

            $vente->lignes()->create([
                'produit_id' => $produit->id,
                'quantite' => $quantite,
                'prix_unitaire' => $produit->prix_vente,
            ]);

            $vente->mouvementsStock()->create([
                'produit_id' => $produit->id,
                'point_de_vente_id' => $pointDeVente->id,
                'type' => MouvementStock::TYPE_SORTIE_VENTE,
                'quantite' => $quantite,
            ]);

            $vente->paiements()->create([
                'montant' => $montantPaye,
                'mode' => Paiement::MODE_ESPECES,
            ]);

            if ($montantPaye < $montantTotal) {
                $clientModel = Client::create([
                    'point_de_vente_id' => $pointDeVente->id,
                    'nom' => $client['nom'],
                    'telephone' => $client['telephone'] ?? null,
                ]);

                $montantInitial = round($montantTotal - $montantPaye, 2);

                $vente->creance()->create([
                    'client_id' => $clientModel->id,
                    'montant_initial' => $montantInitial,
                    'statut' => $montantInitial <= 0 ? Creance::STATUT_SOLDEE : Creance::STATUT_OUVERTE,
                ]);
            }

            return $vente;
        });
    }
}
