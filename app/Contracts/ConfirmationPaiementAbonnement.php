<?php

namespace App\Contracts;

/**
 * The only information the domain needs from a subscription payment
 * confirmation, regardless of which payment provider produced it.
 */
final readonly class ConfirmationPaiementAbonnement
{
    public function __construct(
        public float $montant,
        public string $referenceExterne,
    ) {}
}
