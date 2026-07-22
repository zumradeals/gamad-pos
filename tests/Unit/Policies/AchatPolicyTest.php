<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\Achat;
use App\Models\Entreprise;
use App\Models\User;
use App\Policies\AchatPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchatPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AchatPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AchatPolicy;
    }

    public function test_a_proprietaire_can_create_and_view_an_achat_of_their_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $achat = Achat::factory()->create(['entreprise_id' => $entreprise->id]);

        $this->assertTrue($this->policy->creer($proprietaire));
        $this->assertTrue($this->policy->voir($proprietaire, $achat));
    }

    public function test_a_magasinier_cannot_create_or_view_an_achat_at_this_stage(): void
    {
        $entreprise = Entreprise::factory()->create();
        $magasinier = User::factory()->pourEntreprise($entreprise, RoleEnum::Magasinier)->create();
        $achat = Achat::factory()->create(['entreprise_id' => $entreprise->id]);

        $this->assertFalse($this->policy->creer($magasinier));
        $this->assertFalse($this->policy->voir($magasinier, $achat));
    }

    public function test_a_vendeur_cannot_create_or_view_an_achat(): void
    {
        $entreprise = Entreprise::factory()->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $achat = Achat::factory()->create(['entreprise_id' => $entreprise->id]);

        $this->assertFalse($this->policy->creer($vendeur));
        $this->assertFalse($this->policy->voir($vendeur, $achat));
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_view_the_achat(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $achat = Achat::factory()->create(['entreprise_id' => $entrepriseA->id]);

        $this->assertFalse($this->policy->voir($proprietaireEtranger, $achat));
    }
}
