<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\User;
use App\Policies\FournisseurPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurPolicyTest extends TestCase
{
    use RefreshDatabase;

    private FournisseurPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FournisseurPolicy;
    }

    public function test_a_proprietaire_can_create_and_modify_a_fournisseur_of_their_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $fournisseur = Fournisseur::factory()->for($entreprise)->create();

        $this->assertTrue($this->policy->creer($proprietaire));
        $this->assertTrue($this->policy->modifier($proprietaire, $fournisseur));
        $this->assertTrue($this->policy->voir($proprietaire, $fournisseur));
    }

    public function test_a_vendeur_cannot_create_or_modify_a_fournisseur(): void
    {
        $entreprise = Entreprise::factory()->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $fournisseur = Fournisseur::factory()->for($entreprise)->create();

        $this->assertFalse($this->policy->creer($vendeur));
        $this->assertFalse($this->policy->modifier($vendeur, $fournisseur));
    }

    public function test_a_magasinier_cannot_create_or_modify_a_fournisseur_at_this_stage(): void
    {
        $entreprise = Entreprise::factory()->create();
        $magasinier = User::factory()->pourEntreprise($entreprise, RoleEnum::Magasinier)->create();
        $fournisseur = Fournisseur::factory()->for($entreprise)->create();

        $this->assertFalse($this->policy->creer($magasinier));
        $this->assertFalse($this->policy->modifier($magasinier, $fournisseur));
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_modify_the_fournisseur(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $fournisseur = Fournisseur::factory()->for($entrepriseA)->create();

        $this->assertFalse($this->policy->modifier($proprietaireEtranger, $fournisseur));
        $this->assertFalse($this->policy->voir($proprietaireEtranger, $fournisseur));
    }
}
