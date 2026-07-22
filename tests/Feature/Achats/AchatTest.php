<?php

namespace Tests\Feature\Achats;

use App\Enums\RoleEnum;
use App\Models\Achat;
use App\Models\Depot;
use App\Models\DetteFournisseur;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchatTest extends TestCase
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
        $this->produit = Produit::factory()->for($this->entreprise)->create(['prix_achat' => null]);

        $this->actingAs($this->proprietaire);
    }

    public function test_a_fully_paid_achat_receives_stock_and_creates_no_debt(): void
    {
        $this->post('/achats', [
            'fournisseur_id' => $this->fournisseur->id,
            'depot_id' => $this->depot->id,
            'montant_paye' => 5000,
            'lignes' => [
                ['produit_id' => $this->produit->id, 'quantite' => 10, 'prix_unitaire' => 500],
            ],
        ])->assertRedirect();

        $achat = Achat::firstOrFail();

        $this->assertSame(5000.0, (float) $achat->montant_total);
        $this->assertSame($this->fournisseur->id, $achat->fournisseur_id);
        $this->assertDatabaseCount('dettes_fournisseur', 0);
        $this->assertSame(10.0, $this->produit->fresh()->stockDisponible($this->depot));
        $this->assertSame(500.0, (float) $this->produit->fresh()->prix_achat);
    }

    public function test_a_partially_paid_achat_creates_a_debt_for_the_exact_balance_and_updates_prix_achat(): void
    {
        $this->post('/achats', [
            'fournisseur_id' => $this->fournisseur->id,
            'depot_id' => $this->depot->id,
            'montant_paye' => 2000,
            'lignes' => [
                ['produit_id' => $this->produit->id, 'quantite' => 10, 'prix_unitaire' => 500],
            ],
        ])->assertRedirect();

        $achat = Achat::firstOrFail();
        $dette = DetteFournisseur::firstOrFail();

        $this->assertSame($achat->id, $dette->achat_id);
        $this->assertSame($this->fournisseur->id, $dette->fournisseur_id);
        $this->assertSame(3000.0, (float) $dette->montant_initial);
        $this->assertSame(DetteFournisseur::STATUT_OUVERTE, $dette->statut);
        $this->assertSame(500.0, (float) $this->produit->fresh()->prix_achat);
    }

    public function test_the_received_stock_is_available_at_the_correct_emplacement(): void
    {
        $autreDepot = Depot::factory()->for($this->entreprise)->create();

        $this->post('/achats', [
            'fournisseur_id' => $this->fournisseur->id,
            'depot_id' => $this->depot->id,
            'montant_paye' => 5000,
            'lignes' => [
                ['produit_id' => $this->produit->id, 'quantite' => 10, 'prix_unitaire' => 500],
            ],
        ])->assertRedirect();

        $achat = Achat::firstOrFail();

        $this->assertDatabaseHas('mouvements_stock', [
            'produit_id' => $this->produit->id,
            'emplacement_type' => Depot::class,
            'emplacement_id' => $this->depot->id,
            'type' => 'reception',
            'quantite' => 10,
            'origine_type' => Achat::class,
            'origine_id' => $achat->id,
        ]);

        $this->assertSame(10.0, $this->produit->fresh()->stockDisponible($this->depot));
        $this->assertSame(0.0, $this->produit->fresh()->stockDisponible($autreDepot));
    }

    public function test_a_non_proprietaire_cannot_create_an_achat(): void
    {
        $magasinier = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Magasinier)->create();

        $this->actingAs($magasinier)->post('/achats', [
            'fournisseur_id' => $this->fournisseur->id,
            'depot_id' => $this->depot->id,
            'montant_paye' => 5000,
            'lignes' => [
                ['produit_id' => $this->produit->id, 'quantite' => 10, 'prix_unitaire' => 500],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('achats', 0);
    }
}
