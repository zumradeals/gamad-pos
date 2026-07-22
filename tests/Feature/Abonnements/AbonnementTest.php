<?php

namespace Tests\Feature\Abonnements;

use App\Contracts\PaiementAbonnementProvider;
use App\Enums\RoleEnum;
use App\Models\Abonnement;
use App\Models\Entreprise;
use App\Models\Offre;
use App\Models\PaiementAbonnement;
use App\Models\User;
use App\Services\AbonnementService;
use App\Services\FakePaiementAbonnementProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbonnementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_entreprise_has_no_active_subscription_by_default(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->assertDatabaseCount('abonnements', 0);
        $this->assertFalse(
            Abonnement::where('entreprise_id', $entreprise->id)->where('statut', Abonnement::STATUT_ACTIF)->exists()
        );
    }

    public function test_activating_through_the_fake_provider_creates_an_active_abonnement_and_a_recorded_paiement(): void
    {
        $entreprise = Entreprise::factory()->create();
        $offre = Offre::factory()->create(['code' => Offre::SOLO]);

        $provider = new FakePaiementAbonnementProvider;
        $confirmation = $provider->confirmer(['montant' => 15000, 'reference_externe' => 'ref-001']);

        $abonnement = app(AbonnementService::class)->activer($entreprise, $offre, $confirmation);

        $this->assertSame(Abonnement::STATUT_ACTIF, $abonnement->statut);
        $this->assertSame($entreprise->id, $abonnement->entreprise_id);
        $this->assertSame($offre->id, $abonnement->offre_id);
        $this->assertNotNull($abonnement->paiement_origine_id);

        $paiement = PaiementAbonnement::firstOrFail();
        $this->assertSame('ref-001', $paiement->reference_externe);
        $this->assertSame(15000.0, (float) $paiement->montant);
        $this->assertSame(PaiementAbonnement::STATUT_CONFIRME, $paiement->statut);
        $this->assertSame($abonnement->id, $paiement->abonnement_id);
        $this->assertSame($paiement->id, $abonnement->paiement_origine_id);
    }

    public function test_two_confirmations_with_the_same_reference_externe_are_idempotent(): void
    {
        $entreprise = Entreprise::factory()->create();
        $offre = Offre::factory()->create(['code' => Offre::SOLO]);

        $provider = new FakePaiementAbonnementProvider;
        $confirmation = $provider->confirmer(['montant' => 15000, 'reference_externe' => 'ref-idempotence']);

        $abonnements = app(AbonnementService::class);
        $premier = $abonnements->activer($entreprise, $offre, $confirmation);
        $second = $abonnements->activer($entreprise, $offre, $confirmation);

        $this->assertSame($premier->id, $second->id);
        $this->assertDatabaseCount('abonnements', 1);
        $this->assertDatabaseCount('paiements_abonnement', 1);
    }

    public function test_activating_for_an_entreprise_with_an_already_active_abonnement_prolongs_it_instead_of_creating_a_second_one(): void
    {
        $entreprise = Entreprise::factory()->create();
        $offre = Offre::factory()->create(['code' => Offre::SOLO]);

        $provider = new FakePaiementAbonnementProvider;
        $abonnements = app(AbonnementService::class);

        $premier = $abonnements->activer($entreprise, $offre, $provider->confirmer(['montant' => 15000, 'reference_externe' => 'ref-mois-1']));
        $echeanceInitiale = $premier->date_echeance;

        $renouvele = $abonnements->activer($entreprise, $offre, $provider->confirmer(['montant' => 15000, 'reference_externe' => 'ref-mois-2']));

        $this->assertSame($premier->id, $renouvele->id);
        $this->assertDatabaseCount('abonnements', 1);
        $this->assertDatabaseCount('paiements_abonnement', 2);
        $this->assertTrue($renouvele->date_echeance->greaterThan($echeanceInitiale));
        $this->assertSame($premier->paiement_origine_id, $renouvele->paiement_origine_id);
    }

    public function test_the_minimal_endpoint_activates_a_subscription_via_the_bound_provider(): void
    {
        $entreprise = Entreprise::factory()->create();
        $offre = Offre::factory()->create(['code' => Offre::COMMERCE]);
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();

        $this->assertInstanceOf(FakePaiementAbonnementProvider::class, app(PaiementAbonnementProvider::class));

        $this->actingAs($proprietaire)->post('/abonnements/activer', [
            'offre_id' => $offre->id,
            'montant' => 30000,
            'reference_externe' => 'ref-http-001',
        ])->assertRedirect();

        $abonnement = Abonnement::firstOrFail();
        $this->assertSame(Abonnement::STATUT_ACTIF, $abonnement->statut);
        $this->assertSame($entreprise->id, $abonnement->entreprise_id);
        $this->assertDatabaseHas('paiements_abonnement', ['reference_externe' => 'ref-http-001']);
    }
}
