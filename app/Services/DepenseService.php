<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DepenseService
{
    /**
     * Validate a previously registered dépense — simple single-step
     * transition (enregistree -> validee), no multi-level workflow.
     */
    public function valider(Depense $depense, User $validePar): Depense
    {
        if ($depense->statut !== Depense::STATUT_ENREGISTREE) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une dépense enregistrée peut être validée.',
            ]);
        }

        $depense->update([
            'statut' => Depense::STATUT_VALIDEE,
            'validee_par_user_id' => $validePar->id,
        ]);

        return $depense;
    }
}
