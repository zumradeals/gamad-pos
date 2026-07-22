<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\Livraison;
use App\Models\PointDeVente;
use App\Models\User;
use App\Models\Vente;
use App\Policies\LivraisonPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivraisonPolicyTest extends TestCase
{
    use RefreshDatabase;

    private LivraisonPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new LivraisonPolicy;
    }

    private function creerLivraison(Entreprise $entreprise, ?User $responsable = null): Livraison
    {
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $vente = Vente::factory()->for($pointDeVente)->create();

        return Livraison::factory()
            ->for($vente)
            ->create(['responsable_user_id' => $responsable?->id]);
    }

    public function test_the_assigned_livreur_can_mark_the_livraison_as_delivered(): void
    {
        $entreprise = Entreprise::factory()->create();
        $livreur = User::factory()->pourEntreprise($entreprise, RoleEnum::Livreur)->create();
        $livraison = $this->creerLivraison($entreprise, $livreur);

        $this->assertTrue($this->policy->livrer($livreur, $livraison));
    }

    public function test_a_livreur_who_is_not_the_responsable_cannot_mark_the_livraison_as_delivered(): void
    {
        $entreprise = Entreprise::factory()->create();
        $livreurResponsable = User::factory()->pourEntreprise($entreprise, RoleEnum::Livreur)->create();
        $autreLivreur = User::factory()->pourEntreprise($entreprise, RoleEnum::Livreur)->create();
        $livraison = $this->creerLivraison($entreprise, $livreurResponsable);

        $this->assertFalse($this->policy->livrer($autreLivreur, $livraison));
    }

    public function test_a_proprietaire_can_act_on_any_livraison_of_their_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $livreur = User::factory()->pourEntreprise($entreprise, RoleEnum::Livreur)->create();
        $livraison = $this->creerLivraison($entreprise, $livreur);

        $this->assertTrue($this->policy->livrer($proprietaire, $livraison));
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_act_on_the_livraison(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $livraison = $this->creerLivraison($entrepriseA);

        $this->assertFalse($this->policy->livrer($proprietaireEtranger, $livraison));
    }

    public function test_a_proprietaire_can_assign_a_responsable_livreur(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $livraison = $this->creerLivraison($entreprise);

        $this->assertTrue($this->policy->assigner($proprietaire, $livraison));
    }

    public function test_a_non_proprietaire_cannot_assign_a_responsable_livreur(): void
    {
        $entreprise = Entreprise::factory()->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $livraison = $this->creerLivraison($entreprise);

        $this->assertFalse($this->policy->assigner($vendeur, $livraison));
    }
}
