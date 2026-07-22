<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\PointDeVente;
use App\Models\User;
use App\Policies\RapportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RapportPolicyTest extends TestCase
{
    use RefreshDatabase;

    private RapportPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RapportPolicy;
    }

    public function test_a_proprietaire_can_view_the_rapport_of_a_point_de_vente_of_their_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();

        $this->assertTrue($this->policy->voir($proprietaire, $pointDeVente));
    }

    public function test_a_non_proprietaire_cannot_view_the_rapport(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();

        foreach ([RoleEnum::Vendeur, RoleEnum::Caissier, RoleEnum::Magasinier, RoleEnum::Livreur] as $role) {
            $user = User::factory()->pourEntreprise($entreprise, $role)->create();

            $this->assertFalse($this->policy->voir($user, $pointDeVente), "Le rôle {$role->value} ne devrait pas voir le rapport.");
        }
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_view_the_rapport(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $pointDeVente = PointDeVente::factory()->for($entrepriseA)->create();

        $this->assertFalse($this->policy->voir($proprietaireEtranger, $pointDeVente));
    }
}
