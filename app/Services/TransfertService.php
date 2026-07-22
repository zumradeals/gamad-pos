<?php

namespace App\Services;

use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransfertService
{
    /**
     * Initiate a transfer between two emplacements. The source is debited
     * immediately (its stock becomes unavailable right away). The
     * destination movement is created in the same instant but doesn't count
     * towards available stock until it is receptionne — the merchandise is
     * in transit, unavailable at both ends until confirmed.
     */
    public function initier(Produit $produit, Model $source, Model $destination, float $quantite): MouvementStock
    {
        if ($produit->stockDisponible($source) < $quantite) {
            throw ValidationException::withMessages([
                'quantite' => 'Stock disponible insuffisant à l\'emplacement source pour ce transfert.',
            ]);
        }

        return DB::transaction(function () use ($produit, $source, $destination, $quantite) {
            $sortie = $produit->mouvementsStock()->create([
                'emplacement_type' => $source::class,
                'emplacement_id' => $source->id,
                'type' => MouvementStock::TYPE_TRANSFERT_SORTIE,
                'quantite' => $quantite,
            ]);

            return $produit->mouvementsStock()->create([
                'emplacement_type' => $destination::class,
                'emplacement_id' => $destination->id,
                'type' => MouvementStock::TYPE_TRANSFERT_ENTREE,
                'quantite' => $quantite,
                'receptionne_at' => null,
                'origine_type' => MouvementStock::class,
                'origine_id' => $sortie->id,
            ]);
        });
    }

    /**
     * Confirm the arrival of an in-transit transfer, making the stock
     * available at destination from this point on.
     */
    public function receptionner(MouvementStock $transfertEntree): MouvementStock
    {
        if ($transfertEntree->type !== MouvementStock::TYPE_TRANSFERT_ENTREE) {
            throw ValidationException::withMessages([
                'mouvement' => 'Ce mouvement n\'est pas une entrée de transfert.',
            ]);
        }

        if ($transfertEntree->receptionne_at !== null) {
            throw ValidationException::withMessages([
                'mouvement' => 'Ce transfert a déjà été réceptionné.',
            ]);
        }

        $transfertEntree->update(['receptionne_at' => now()]);

        return $transfertEntree;
    }
}
