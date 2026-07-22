<?php

namespace Tests\Feature\Depenses;

use App\Enums\RoleEnum;
use App\Models\Cloture;
use App\Models\Depense;
use App\Models\Entreprise;
use App\Models\PointDeVente;
use App\Models\User;
use App\Services\ClotureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepenseTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private PointDeVente $pointDeVente;

    private User $caissier;

    private User $proprietaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($this->entreprise)->create();

        $this->caissier = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Caissier)->create();
        $this->proprietaire = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Proprietaire)->create();
    }

    public function test_a_depense_registered_by_a_caissier_can_be_validated_by_a_proprietaire(): void
    {
        $this->actingAs($this->caissier)
            ->withSession(['point_de_vente_id' => $this->pointDeVente->id])
            ->post('/depenses', [
                'categorie' => 'transport',
                'montant' => 1500,
                'justificatif' => 'REF-0001',
            ])->assertRedirect();

        $depense = Depense::firstOrFail();
        $this->assertSame(Depense::STATUT_ENREGISTREE, $depense->statut);
        $this->assertSame($this->caissier->id, $depense->user_id);
        $this->assertNull($depense->validee_par_user_id);

        $this->actingAs($this->proprietaire)
            ->post("/depenses/{$depense->id}/valider")
            ->assertRedirect();

        $depense->refresh();
        $this->assertSame(Depense::STATUT_VALIDEE, $depense->statut);
        $this->assertSame($this->proprietaire->id, $depense->validee_par_user_id);
    }

    public function test_a_caissier_cannot_validate_a_depense(): void
    {
        $depense = Depense::factory()->for($this->pointDeVente)->create();

        $this->actingAs($this->caissier)
            ->post("/depenses/{$depense->id}/valider")
            ->assertForbidden();

        $this->assertSame(Depense::STATUT_ENREGISTREE, $depense->fresh()->statut);
    }

    public function test_a_vendeur_cannot_register_a_depense(): void
    {
        $vendeur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Vendeur)->create();

        $this->actingAs($vendeur)
            ->withSession(['point_de_vente_id' => $this->pointDeVente->id])
            ->post('/depenses', [
                'categorie' => 'transport',
                'montant' => 1500,
            ])->assertForbidden();

        $this->assertDatabaseCount('depenses', 0);
    }

    public function test_an_unvalidated_depense_is_not_counted_in_the_cloture(): void
    {
        Depense::factory()->for($this->pointDeVente)->create([
            'montant' => 2000,
            'statut' => Depense::STATUT_ENREGISTREE,
        ]);

        $clotures = app(ClotureService::class);
        $this->assertSame(0.0, $clotures->depensesTotal($this->pointDeVente));

        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $cloture = $clotures->valider($cloture, 0, $this->caissier);

        $this->assertSame(0.0, (float) $cloture->depenses_total);
        $this->assertDatabaseCount('depenses', 1);
        $this->assertNull(Depense::firstOrFail()->cloture_id);
    }

    public function test_a_validated_depense_is_included_in_depenses_total_and_attached_to_the_cloture_never_recounted(): void
    {
        $depense = Depense::factory()->for($this->pointDeVente)->create([
            'montant' => 3000,
            'statut' => Depense::STATUT_VALIDEE,
            'validee_par_user_id' => $this->proprietaire->id,
        ]);

        $clotures = app(ClotureService::class);
        $this->assertSame(3000.0, $clotures->depensesTotal($this->pointDeVente));

        $premiere = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $premiere = $clotures->valider($premiere, 0, $this->caissier);

        $this->assertSame(3000.0, (float) $premiere->depenses_total);
        $this->assertSame($premiere->id, $depense->fresh()->cloture_id);

        // A new validated dépense after the first clôture.
        Depense::factory()->for($this->pointDeVente)->create([
            'montant' => 500,
            'statut' => Depense::STATUT_VALIDEE,
            'validee_par_user_id' => $this->proprietaire->id,
        ]);

        // Only the new dépense counts — the first one is already rattachée.
        $this->assertSame(500.0, $clotures->depensesTotal($this->pointDeVente));

        $deuxieme = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $deuxieme = $clotures->valider($deuxieme, 0, $this->caissier);

        $this->assertSame(500.0, (float) $deuxieme->depenses_total);
        $this->assertSame(Cloture::STATUT_VALIDEE, $deuxieme->statut);
    }
}
