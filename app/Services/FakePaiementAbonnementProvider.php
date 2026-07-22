<?php

namespace App\Services;

use App\Contracts\ConfirmationPaiementAbonnement;
use App\Contracts\PaiementAbonnementProvider;
use Illuminate\Support\Str;

/**
 * Stands in for a real payment provider during all development and testing
 * for this chantier — no external integration exists yet. A future chantier
 * introduces the real implementation behind the same interface; nothing
 * else in the domain changes when that happens.
 */
class FakePaiementAbonnementProvider implements PaiementAbonnementProvider
{
    public function confirmer(array $signal): ConfirmationPaiementAbonnement
    {
        return new ConfirmationPaiementAbonnement(
            montant: (float) ($signal['montant'] ?? 0),
            referenceExterne: (string) ($signal['reference_externe'] ?? (string) Str::uuid()),
        );
    }
}
