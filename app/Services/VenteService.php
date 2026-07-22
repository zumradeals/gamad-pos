<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Creance;
use App\Models\Livraison;
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
     * becomes a debt (créance) owed by that client. Handover may likewise be
     * deferred to a later livraison instead of happening immediately, which
     * also requires a client. A single client is created and shared by the
     * créance and the livraison when both apply to the same sale. A full,
     * immediate sale never creates a debt or a livraison, regardless of
     * whether a client was supplied. Writes the sale, its line, the resulting
     * stock movement, the payment and (when applicable) the debt and the
     * livraison atomically.
     *
     * @param  array{nom: string, telephone: ?string}|null  $client
     * @param  array{lieu: string, date_prevue: ?string}|null  $livraison
     */
    public function enregistrerVente(
        User $vendeur,
        PointDeVente $pointDeVente,
        Produit $produit,
        float $quantite,
        float $montantPaye,
        ?array $client = null,
        ?array $livraison = null,
    ): Vente {
        $montantTotal = round((float) $produit->prix_vente * $quantite, 2);
        $creeCreance = $montantPaye < $montantTotal;
        $livraisonDifferee = $livraison !== null;

        if (($creeCreance || $livraisonDifferee) && $client === null) {
            throw ValidationException::withMessages([
                'client' => 'Un client doit être renseigné pour un paiement partiel ou une livraison différée.',
            ]);
        }

        if ($produit->stockDisponible() < $quantite) {
            throw ValidationException::withMessages([
                'quantite' => 'Stock disponible insuffisant pour cette quantité.',
            ]);
        }

        return DB::transaction(function () use ($vendeur, $pointDeVente, $produit, $quantite, $montantTotal, $montantPaye, $client, $livraison, $creeCreance, $livraisonDifferee) {
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

            $clientModel = ($creeCreance || $livraisonDifferee)
                ? Client::create([
                    'point_de_vente_id' => $pointDeVente->id,
                    'nom' => $client['nom'],
                    'telephone' => $client['telephone'] ?? null,
                ])
                : null;

            if ($creeCreance) {
                $montantInitial = round($montantTotal - $montantPaye, 2);

                $vente->creance()->create([
                    'client_id' => $clientModel->id,
                    'montant_initial' => $montantInitial,
                    'statut' => $montantInitial <= 0 ? Creance::STATUT_SOLDEE : Creance::STATUT_OUVERTE,
                ]);
            }

            if ($livraisonDifferee) {
                $vente->livraison()->create([
                    'client_id' => $clientModel->id,
                    'lieu' => $livraison['lieu'],
                    'date_prevue' => $livraison['date_prevue'] ?? null,
                    'statut' => Livraison::STATUT_PLANIFIEE,
                ]);
            }

            return $vente;
        });
    }
}
