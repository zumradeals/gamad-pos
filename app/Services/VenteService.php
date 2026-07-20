<?php

namespace App\Services;

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
     * Register a cash sale for a single product line, paid in full. The sale is
     * validated immediately: no debt, no partial payment. Writes the sale, its
     * line, the resulting stock movement and the payment atomically.
     */
    public function enregistrerVenteComptant(
        User $vendeur,
        PointDeVente $pointDeVente,
        Produit $produit,
        float $quantite,
        float $montantPaye,
    ): Vente {
        $montantTotal = (float) $produit->prix_vente * $quantite;

        if ($montantPaye < $montantTotal) {
            throw ValidationException::withMessages([
                'montant_paye' => 'Le paiement doit couvrir l\'intégralité du montant de la vente.',
            ]);
        }

        if ($produit->stockDisponible() < $quantite) {
            throw ValidationException::withMessages([
                'quantite' => 'Stock disponible insuffisant pour cette quantité.',
            ]);
        }

        return DB::transaction(function () use ($vendeur, $pointDeVente, $produit, $quantite, $montantTotal, $montantPaye) {
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

            return $vente;
        });
    }
}
