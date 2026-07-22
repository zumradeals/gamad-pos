<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\Depense;
use App\Models\Entreprise;
use App\Models\PointDeVente;
use App\Models\User;
use App\Policies\DepensePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepensePolicyTest extends TestCase
{
    use RefreshDatabase;

    private DepensePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DepensePolicy;
    }

    public function test_a_caissier_can_create_a_depense_but_not_validate_it(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $caissier = User::factory()->pourEntreprise($entreprise, RoleEnum::Caissier)->create();
        $depense = Depense::factory()->for($pointDeVente)->create();

        $this->assertTrue($this->policy->creer($caissier));
        $this->assertFalse($this->policy->valider($caissier, $depense));
    }

    public function test_a_proprietaire_can_create_and_validate_a_depense_of_their_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $depense = Depense::factory()->for($pointDeVente)->create();

        $this->assertTrue($this->policy->creer($proprietaire));
        $this->assertTrue($this->policy->valider($proprietaire, $depense));
    }

    public function test_a_vendeur_can_neither_create_nor_validate_a_depense(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $depense = Depense::factory()->for($pointDeVente)->create();

        $this->assertFalse($this->policy->creer($vendeur));
        $this->assertFalse($this->policy->valider($vendeur, $depense));
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_validate_the_depense(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entrepriseA)->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $depense = Depense::factory()->for($pointDeVente)->create();

        $this->assertFalse($this->policy->valider($proprietaireEtranger, $depense));
    }
}
