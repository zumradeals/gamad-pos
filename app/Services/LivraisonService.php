<?php

namespace App\Services;

use App\Models\LigneLivraison;
use App\Models\Livraison;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LivraisonService
{
    /**
     * Register a delivery pass against a livraison. The quantity can never
     * exceed what's left to deliver — it is rejected outright rather than
     * capped. The livraison is marked livree once nothing remains to
     * deliver, or partielle as long as a positive balance remains after at
     * least one pass.
     */
    public function enregistrerLigneLivraison(Livraison $livraison, float $quantite): LigneLivraison
    {
        $reste = $livraison->resteALivrer();

        if (round($quantite - $reste, 2) > 0) {
            throw ValidationException::withMessages([
                'quantite' => 'La quantité dépasse le reste à livrer.',
            ]);
        }

        return DB::transaction(function () use ($livraison, $quantite, $reste) {
            $ligne = $livraison->lignesLivraison()->create([
                'quantite' => $quantite,
                'date' => now(),
            ]);

            $nouveauReste = round($reste - $quantite, 2);

            $livraison->update([
                'statut' => $nouveauReste <= 0 ? Livraison::STATUT_LIVREE : Livraison::STATUT_PARTIELLE,
            ]);

            return $ligne;
        });
    }
}
