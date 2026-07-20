<?php

namespace App\Services;

use App\Models\Creance;
use App\Models\Versement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreanceService
{
    /**
     * Register a payment against an existing debt. The payment can never
     * exceed the outstanding balance — it is rejected outright rather than
     * capped. When it brings the balance to exactly zero, the debt is marked
     * as settled.
     */
    public function enregistrerVersement(Creance $creance, float $montant): Versement
    {
        $resteDu = $creance->resteDu();

        if (round($montant - $resteDu, 2) > 0) {
            throw ValidationException::withMessages([
                'montant' => 'Le versement dépasse le reste dû.',
            ]);
        }

        return DB::transaction(function () use ($creance, $montant, $resteDu) {
            $versement = $creance->versements()->create([
                'montant' => $montant,
                'mode' => Versement::MODE_ESPECES,
            ]);

            if (round($resteDu - $montant, 2) <= 0) {
                $creance->update(['statut' => Creance::STATUT_SOLDEE]);
            }

            return $versement;
        });
    }
}
