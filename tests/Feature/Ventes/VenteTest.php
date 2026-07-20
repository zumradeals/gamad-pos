<?php

namespace Tests\Feature\Ventes;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\MouvementStock;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenteTest extends TestCase
{
    use RefreshDatabase;

    private PointDeVente $pointDeVente;

    private User $vendeur;

    protected function setUp(): void
    {
        parent::setUp();

        $entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($entreprise)->create();

        $this->vendeur = User::factory()
            ->pourEntreprise($entreprise, RoleEnum::Vendeur)
            ->create();
        $this->vendeur->pointsDeVente()->attach($this->pointDeVente->id);

        $this->actingAs($this->vendeur);
        $this->withSession(['point_de_vente_id' => $this->pointDeVente->id]);
    }

    private function creerProduitAvecStock(string $nom, float $prixVente, float $stockInitial, string $unite = 'unite'): Produit
    {
        $this->post('/produits', [
            'nom' => $nom,
            'prix_vente' => $prixVente,
            'unite' => $unite,
            'quantite_initiale' => $stockInitial,
        ])->assertRedirect();

        return Produit::where('nom', $nom)->firstOrFail();
    }

    public function test_creating_a_produit_with_initial_stock_then_selling_decreases_available_stock_by_the_exact_quantity(): void
    {
        $produit = $this->creerProduitAvecStock('Savon', 500, 20);

        $this->assertSame(20.0, $produit->stockDisponible());

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 3,
            'montant_paye' => 1500,
        ])->assertRedirect();

        $this->assertSame(17.0, $produit->fresh()->stockDisponible());
    }

    public function test_a_validated_vente_creates_a_traceable_sortie_vente_stock_movement(): void
    {
        $produit = $this->creerProduitAvecStock('Riz', 1000, 50);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 5000,
        ])->assertRedirect();

        $vente = Vente::firstOrFail();

        $this->assertDatabaseHas('mouvements_stock', [
            'produit_id' => $produit->id,
            'point_de_vente_id' => $this->pointDeVente->id,
            'type' => MouvementStock::TYPE_SORTIE_VENTE,
            'quantite' => 5,
            'origine_type' => Vente::class,
            'origine_id' => $vente->id,
        ]);
    }

    public function test_no_route_allows_deleting_a_validated_vente(): void
    {
        $vente = Vente::factory()
            ->for($this->pointDeVente)
            ->for($this->vendeur, 'vendeur')
            ->create(['statut' => Vente::STATUT_VALIDEE]);

        $this->delete("/ventes/{$vente->id}")->assertNotFound();

        $this->assertDatabaseHas('ventes', ['id' => $vente->id]);
    }

    public function test_a_payment_lower_than_the_total_amount_without_a_client_is_rejected(): void
    {
        $produit = $this->creerProduitAvecStock('Huile', 2000, 10);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 3000,
        ])->assertSessionHasErrors('client');

        $this->assertDatabaseMissing('ventes', ['point_de_vente_id' => $this->pointDeVente->id]);
        $this->assertSame(10.0, $produit->fresh()->stockDisponible());
    }
}
