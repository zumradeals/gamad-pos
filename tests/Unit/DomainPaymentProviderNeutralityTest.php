<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DomainPaymentProviderNeutralityTest extends TestCase
{
    /**
     * Invariant F1: the payment provider is an integration detail, never a
     * domain concept — no class under Services, Models or Policies may name
     * one. Enforced here instead of merely respected by discipline.
     */
    public function test_no_domain_class_references_a_named_payment_provider(): void
    {
        $prestatairesInterdits = [
            'Genius\s*Pay',
            'Wave',
            'Orange\s*Money',
            'MTN\s*Money',
            'Moov\s*Money',
            'Stripe',
            'PayPal',
            'CinetPay',
            'PayDunya',
            'Flutterwave',
        ];

        $dossiers = [app_path('Services'), app_path('Models'), app_path('Policies')];
        $violations = [];

        foreach ($dossiers as $dossier) {
            if (! is_dir($dossier)) {
                continue;
            }

            foreach (File::allFiles($dossier) as $fichier) {
                $contenu = File::get($fichier->getPathname());

                foreach ($prestatairesInterdits as $motif) {
                    if (preg_match('/\b'.$motif.'\b/i', $contenu) === 1) {
                        $violations[] = $fichier->getRelativePathname()." references a payment provider matching /{$motif}/i";
                    }
                }
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }
}
