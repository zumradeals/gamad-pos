<?php

namespace Tests\Feature\Fournisseurs;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private User $proprietaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->proprietaire = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Proprietaire)->create();
    }

    public function test_a_proprietaire_can_create_list_and_update_a_fournisseur(): void
    {
        $this->actingAs($this->proprietaire)->post('/fournisseurs', [
            'nom' => 'Grossiste Sahel',
            'telephone' => '+22170000000',
            'conditions_commerciales' => 'Paiement à 30 jours',
            'delais_habituels' => '10 jours',
        ])->assertRedirect();

        $fournisseur = Fournisseur::firstOrFail();
        $this->assertSame('Grossiste Sahel', $fournisseur->nom);
        $this->assertSame($this->entreprise->id, $fournisseur->entreprise_id);

        $this->actingAs($this->proprietaire)->getJson('/fournisseurs')
            ->assertOk()
            ->assertJsonCount(1, 'fournisseurs')
            ->assertJsonPath('fournisseurs.0.nom', 'Grossiste Sahel');

        $this->actingAs($this->proprietaire)->patch("/fournisseurs/{$fournisseur->id}", [
            'nom' => 'Grossiste Sahel SARL',
        ])->assertRedirect();

        $this->assertSame('Grossiste Sahel SARL', $fournisseur->fresh()->nom);
    }

    public function test_a_non_proprietaire_cannot_create_or_update_a_fournisseur(): void
    {
        $vendeur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Vendeur)->create();
        $fournisseur = Fournisseur::factory()->for($this->entreprise)->create();

        $this->actingAs($vendeur)->post('/fournisseurs', [
            'nom' => 'Fournisseur non autorisé',
        ])->assertForbidden();

        $this->actingAs($vendeur)->patch("/fournisseurs/{$fournisseur->id}", [
            'nom' => 'Tentative de modification',
        ])->assertForbidden();

        $this->assertDatabaseCount('fournisseurs', 1);
        $this->assertNotSame('Tentative de modification', $fournisseur->fresh()->nom);
    }
}
