<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\DetteFournisseur;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\User;
use App\Policies\DetteFournisseurPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetteFournisseurPolicyTest extends TestCase
{
    use RefreshDatabase;

    private DetteFournisseurPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DetteFournisseurPolicy;
    }

    public function test_a_proprietaire_can_verser_on_a_dette_of_their_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $fournisseur = Fournisseur::factory()->for($entreprise)->create();
        $dette = DetteFournisseur::factory()->for($fournisseur)->create();

        $this->assertTrue($this->policy->verser($proprietaire, $dette));
    }

    public function test_a_magasinier_cannot_verser_on_a_dette_at_this_stage(): void
    {
        $entreprise = Entreprise::factory()->create();
        $magasinier = User::factory()->pourEntreprise($entreprise, RoleEnum::Magasinier)->create();
        $fournisseur = Fournisseur::factory()->for($entreprise)->create();
        $dette = DetteFournisseur::factory()->for($fournisseur)->create();

        $this->assertFalse($this->policy->verser($magasinier, $dette));
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_verser_on_the_dette(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $fournisseur = Fournisseur::factory()->for($entrepriseA)->create();
        $dette = DetteFournisseur::factory()->for($fournisseur)->create();

        $this->assertFalse($this->policy->verser($proprietaireEtranger, $dette));
    }
}
