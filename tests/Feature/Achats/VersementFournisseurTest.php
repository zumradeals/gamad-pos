<?php

namespace Tests\Feature\Achats;

use App\Enums\RoleEnum;
use App\Models\Depot;
use App\Models\DetteFournisseur;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersementFournisseurTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private User $proprietaire;

    private Fournisseur $fournisseur;

    private Depot $depot;

    private Produit $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->proprietaire = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Proprietaire)->create();
        $this->fournisseur = Fournisseur::factory()->for($this->entreprise)->create();
        $this->depot = Depot::factory()->for($this->entreprise)->create();
        $this->produit = Produit::factory()->for($this->entreprise)->create();

        $this->actingAs($this->proprietaire);
    }

    private function creerDetteFournisseur(float $montantTotal, float $montantPaye): DetteFournisseur
    {
        $this->post('/achats', [
            'fournisseur_id' => $this->fournisseur->id,
            'depot_id' => $this->depot->id,
            'montant_paye' => $montantPaye,
            'lignes' => [
                ['produit_id' => $this->produit->id, 'quantite' => 10, 'prix_unitaire' => $montantTotal / 10],
            ],
        ])->assertRedirect();

        return DetteFournisseur::firstOrFail();
    }

    public function test_a_versement_that_brings_the_balance_to_zero_settles_the_debt(): void
    {
        $dette = $this->creerDetteFournisseur(5000, 2000);
        $this->assertSame(3000.0, $dette->resteDu());

        $this->post("/dettes-fournisseur/{$dette->id}/versements", [
            'montant' => 3000,
        ])->assertRedirect();

        $dette->refresh();
        $this->assertSame(0.0, $dette->resteDu());
        $this->assertSame(DetteFournisseur::STATUT_SOLDEE, $dette->statut);
    }

    public function test_a_versement_exceeding_the_balance_is_rejected_and_leaves_it_unchanged(): void
    {
        $dette = $this->creerDetteFournisseur(5000, 2000);
        $resteAvant = $dette->resteDu();

        $this->post("/dettes-fournisseur/{$dette->id}/versements", [
            'montant' => $resteAvant + 1,
        ])->assertSessionHasErrors('montant');

        $dette->refresh();
        $this->assertSame($resteAvant, $dette->resteDu());
        $this->assertSame(DetteFournisseur::STATUT_OUVERTE, $dette->statut);
    }

    public function test_a_non_proprietaire_cannot_register_a_versement(): void
    {
        $dette = $this->creerDetteFournisseur(5000, 2000);
        $magasinier = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Magasinier)->create();

        $this->actingAs($magasinier)->post("/dettes-fournisseur/{$dette->id}/versements", [
            'montant' => 1000,
        ])->assertForbidden();

        $dette->refresh();
        $this->assertSame(3000.0, $dette->resteDu());
    }
}
