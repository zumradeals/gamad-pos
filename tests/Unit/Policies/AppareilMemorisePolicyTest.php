<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleEnum;
use App\Models\AppareilMemorise;
use App\Models\Entreprise;
use App\Models\User;
use App\Policies\AppareilMemorisePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppareilMemorisePolicyTest extends TestCase
{
    use RefreshDatabase;

    private AppareilMemorisePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AppareilMemorisePolicy;
    }

    public function test_a_user_can_revoke_their_own_device(): void
    {
        $entreprise = Entreprise::factory()->create();
        $user = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $appareil = AppareilMemorise::factory()->for($user)->create();

        $this->assertTrue($this->policy->revoke($user, $appareil));
    }

    public function test_a_proprietaire_can_revoke_a_device_of_another_user_in_the_same_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $appareil = AppareilMemorise::factory()->for($vendeur)->create();

        $this->assertTrue($this->policy->revoke($proprietaire, $appareil));
    }

    public function test_a_vendeur_cannot_revoke_another_users_device(): void
    {
        $entreprise = Entreprise::factory()->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $autreVendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();
        $appareil = AppareilMemorise::factory()->for($autreVendeur)->create();

        $this->assertFalse($this->policy->revoke($vendeur, $appareil));
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_revoke_the_device(): void
    {
        $entrepriseA = Entreprise::factory()->create();
        $entrepriseB = Entreprise::factory()->create();

        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseB, RoleEnum::Proprietaire)->create();
        $vendeur = User::factory()->pourEntreprise($entrepriseA, RoleEnum::Vendeur)->create();
        $appareil = AppareilMemorise::factory()->for($vendeur)->create();

        $this->assertFalse($this->policy->revoke($proprietaireEtranger, $appareil));
    }
}
