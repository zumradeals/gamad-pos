<?php

namespace App\Contracts;

/**
 * Invariant F1: the payment provider is an integration detail, never a
 * domain concept. This is the single seam between the two — no class under
 * Services, Models or Policies may know or care which provider implements
 * it. The real integration (webhook, provider API) is a future, separate
 * chantier that will only ever touch an implementation of this interface,
 * never the domain itself.
 */
interface PaiementAbonnementProvider
{
    /**
     * Turn a raw confirmation signal from the provider's integration point
     * (webhook payload, callback params — its shape belongs to that future
     * implementation) into the only two things the domain needs to activate
     * a subscription.
     *
     * @param  array<string, mixed>  $signal
     */
    public function confirmer(array $signal): ConfirmationPaiementAbonnement;
}
