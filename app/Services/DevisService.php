<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Devis;
use App\Models\PointDeVente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevisService
{
    public function __construct(private readonly CommandeService $commandes)
    {
    }

    /**
     * Propose a devis across one or more lines. A devis is a pure price
     * proposal: it never touches stock or caisse, whatever happens to it
     * next — only transformerEnCommande() does, and only once. montant_total
     * is always derived from the lines themselves, never taken from caller
     * input, so the stored total can never drift from its lines.
     *
     * @param  array<int, array{produit_id: int, quantite: float, prix_unitaire: float}>  $lignes
     */
    public function proposer(Client $client, PointDeVente $pointDeVente, array $lignes): Devis
    {
        $montantTotal = round(array_sum(array_map(
            fn (array $ligne) => $ligne['quantite'] * $ligne['prix_unitaire'],
            $lignes
        )), 2);

        return DB::transaction(function () use ($client, $pointDeVente, $lignes, $montantTotal) {
            $devis = Devis::create([
                'client_id' => $client->id,
                'point_de_vente_id' => $pointDeVente->id,
                'statut' => Devis::STATUT_PROPOSE,
                'montant_total' => $montantTotal,
            ]);

            foreach ($lignes as $ligne) {
                $devis->lignes()->create([
                    'produit_id' => $ligne['produit_id'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                ]);
            }

            return $devis;
        });
    }

    /**
     * Accept a devis still in propose state.
     */
    public function accepter(Devis $devis): Devis
    {
        $this->verifierPropose($devis);

        $devis->update(['statut' => Devis::STATUT_ACCEPTE]);

        return $devis;
    }

    /**
     * Refuse a devis still in propose state.
     */
    public function refuser(Devis $devis): Devis
    {
        $this->verifierPropose($devis);

        $devis->update(['statut' => Devis::STATUT_REFUSE]);

        return $devis;
    }

    /**
     * Mark a devis as expired. Explicit action only — no scheduled task
     * expires a devis automatically, same choice as commande cancellation
     * and Chantier 9's abonnement suspension.
     */
    public function expirer(Devis $devis): Devis
    {
        $this->verifierPropose($devis);

        $devis->update(['statut' => Devis::STATUT_EXPIRE]);

        return $devis;
    }

    /**
     * Transform an accepted devis into a commande without re-entry: its
     * lines are copied as-is. Stock is only ever reserved here, at commande
     * creation — never at the devis stage, whatever its statut. A devis can
     * only ever produce one commande (devis_id is unique on commandes).
     */
    public function transformerEnCommande(Devis $devis, float $montantPaye): Commande
    {
        if ($devis->statut !== Devis::STATUT_ACCEPTE) {
            throw ValidationException::withMessages([
                'statut' => 'Seul un devis accepté peut être transformé en commande.',
            ]);
        }

        if ($devis->commande()->exists()) {
            throw ValidationException::withMessages([
                'devis' => 'Ce devis a déjà été transformé en commande.',
            ]);
        }

        $lignes = $devis->lignes->map(fn ($ligne) => [
            'produit_id' => $ligne->produit_id,
            'quantite' => (float) $ligne->quantite,
            'prix_unitaire' => (float) $ligne->prix_unitaire,
        ])->all();

        return $this->commandes->creer(
            client: $devis->client,
            pointDeVente: $devis->pointDeVente,
            lignes: $lignes,
            montantPaye: $montantPaye,
            devisOrigine: $devis,
        );
    }

    private function verifierPropose(Devis $devis): void
    {
        if ($devis->statut !== Devis::STATUT_PROPOSE) {
            throw ValidationException::withMessages([
                'statut' => 'Ce devis a déjà été traité.',
            ]);
        }
    }
}
