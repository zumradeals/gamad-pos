<?php

namespace Tests\Unit;

use App\Models\Abonnement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbonnementSuspensionTest extends TestCase
{
    use RefreshDatabase;

    private function abonnementAvecEcheance(\DateTimeInterface|string $dateEcheance): Abonnement
    {
        return Abonnement::factory()->create([
            'date_debut' => now()->subMonth(),
            'date_echeance' => $dateEcheance,
            'statut' => Abonnement::STATUT_ACTIF,
        ]);
    }

    public function test_an_abonnement_still_within_its_validity_period_is_neither_en_grace_nor_suspendu(): void
    {
        $abonnement = $this->abonnementAvecEcheance(now()->addDays(5)->toDateString());

        $this->assertFalse($abonnement->estEnGrace());
        $this->assertFalse($abonnement->estSuspendu());
    }

    public function test_an_abonnement_past_its_echeance_but_within_the_grace_window_is_en_grace_not_suspendu(): void
    {
        $abonnement = $this->abonnementAvecEcheance(now()->subDays(3)->toDateString());

        $this->assertTrue($abonnement->estEnGrace());
        $this->assertFalse($abonnement->estSuspendu());
    }

    public function test_an_abonnement_past_echeance_plus_grace_is_suspendu_not_en_grace(): void
    {
        $abonnement = $this->abonnementAvecEcheance(now()->subDays(Abonnement::JOURS_GRACE + 1)->toDateString());

        $this->assertFalse($abonnement->estEnGrace());
        $this->assertTrue($abonnement->estSuspendu());
    }
}
