<?php

namespace App\Services;

use App\Models\DetteFournisseur;
use App\Models\VersementFournisseur;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DetteFournisseurService
{
    /**
     * Register a payment against an existing dette fournisseur. The payment
     * can never exceed the outstanding balance — it is rejected outright
     * rather than capped. When it brings the balance to exactly zero, the
     * dette is marked as soldee. Same rigor as CreanceService::enregistrerVersement.
     */
    public function enregistrerVersement(DetteFournisseur $dette, float $montant): VersementFournisseur
    {
        $resteDu = $dette->resteDu();

        if (round($montant - $resteDu, 2) > 0) {
            throw ValidationException::withMessages([
                'montant' => 'Le versement dépasse le reste dû.',
            ]);
        }

        return DB::transaction(function () use ($dette, $montant, $resteDu) {
            $versement = $dette->versements()->create([
                'montant' => $montant,
            ]);

            if (round($resteDu - $montant, 2) <= 0) {
                $dette->update(['statut' => DetteFournisseur::STATUT_SOLDEE]);
            }

            return $versement;
        });
    }
}
