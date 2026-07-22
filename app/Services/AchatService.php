<?php

namespace App\Services;

use App\Models\Achat;
use App\Models\DetteFournisseur;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\PaiementAchat;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AchatService
{
    /**
     * Register a purchase from a fournisseur across one or more lines. Each
     * line generates a reception stock movement at the given emplacement
     * (Depot or PointDeVente — same polymorphic mechanism as Chantier 6) and
     * updates the produit's prix_achat to that line's unit price — the
     * current prix_achat is always the last validated purchase, never a
     * weighted average. When the payment is less than the total, a dette
     * fournisseur is created for the exact remaining balance.
     *
     * @param  array<int, array{produit_id: int, quantite: float, prix_unitaire: float}>  $lignes
     */
    public function enregistrerAchat(
        User $createur,
        Fournisseur $fournisseur,
        Model $emplacement,
        array $lignes,
        float $montantPaye,
    ): Achat {
        $montantTotal = round(array_sum(array_map(
            fn (array $ligne) => $ligne['quantite'] * $ligne['prix_unitaire'],
            $lignes
        )), 2);

        return DB::transaction(function () use ($createur, $fournisseur, $emplacement, $lignes, $montantTotal, $montantPaye) {
            $achat = Achat::create([
                'entreprise_id' => $fournisseur->entreprise_id,
                'fournisseur_id' => $fournisseur->id,
                'user_id' => $createur->id,
                'emplacement_type' => $emplacement::class,
                'emplacement_id' => $emplacement->id,
                'statut' => Achat::STATUT_VALIDEE,
                'montant_total' => $montantTotal,
            ]);

            foreach ($lignes as $ligne) {
                $produit = Produit::findOrFail($ligne['produit_id']);

                $achat->lignes()->create([
                    'produit_id' => $produit->id,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                ]);

                $achat->mouvementsStock()->create([
                    'produit_id' => $produit->id,
                    'emplacement_type' => $emplacement::class,
                    'emplacement_id' => $emplacement->id,
                    'type' => MouvementStock::TYPE_RECEPTION,
                    'quantite' => $ligne['quantite'],
                ]);

                $produit->update(['prix_achat' => $ligne['prix_unitaire']]);
            }

            $achat->paiements()->create([
                'montant' => $montantPaye,
                'mode' => PaiementAchat::MODE_ESPECES,
            ]);

            if ($montantPaye < $montantTotal) {
                $montantInitial = round($montantTotal - $montantPaye, 2);

                $achat->detteFournisseur()->create([
                    'fournisseur_id' => $fournisseur->id,
                    'montant_initial' => $montantInitial,
                    'statut' => DetteFournisseur::STATUT_OUVERTE,
                ]);
            }

            return $achat;
        });
    }
}
