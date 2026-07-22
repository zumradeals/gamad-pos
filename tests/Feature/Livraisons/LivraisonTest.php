<?php

namespace Tests\Feature\Livraisons;

use App\Enums\RoleEnum;
use App\Models\Creance;
use App\Models\Entreprise;
use App\Models\Livraison;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LivraisonTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private PointDeVente $pointDeVente;

    private User $vendeur;

    private User $proprietaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($this->entreprise)->create();

        $this->vendeur = User::factory()
            ->pourEntreprise($this->entreprise, RoleEnum::Vendeur)
            ->create();
        $this->vendeur->pointsDeVente()->attach($this->pointDeVente->id);

        $this->proprietaire = User::factory()
            ->pourEntreprise($this->entreprise, RoleEnum::Proprietaire)
            ->create();

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

    public function test_a_sale_with_deferred_delivery_and_a_client_creates_a_planned_livraison(): void
    {
        $produit = $this->creerProduitAvecStock('Ciment', 5000, 50);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 10,
            'montant_paye' => 50000,
            'client_nom' => 'Awa Diop',
            'livraison_lieu' => 'Marché central',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();

        $this->assertSame('Marché central', $livraison->lieu);
        $this->assertSame(Livraison::STATUT_PLANIFIEE, $livraison->statut);
        $this->assertSame('Awa Diop', $livraison->client->nom);
        $this->assertSame(10.0, $livraison->resteALivrer());
    }

    public function test_a_deferred_delivery_without_a_client_is_rejected_and_creates_nothing(): void
    {
        $produit = $this->creerProduitAvecStock('Tôles', 8000, 30);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 40000,
            'livraison_lieu' => 'Quartier Nord',
        ])->assertSessionHasErrors('client');

        $this->assertDatabaseCount('ventes', 0);
        $this->assertDatabaseCount('livraisons', 0);
    }

    public function test_a_partial_delivery_pass_sets_the_status_to_partielle_and_recomputes_the_remainder(): void
    {
        $produit = $this->creerProduitAvecStock('Sac de riz', 1000, 50);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 20,
            'montant_paye' => 20000,
            'client_nom' => 'Modou Fall',
            'livraison_lieu' => 'Entrepôt',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();

        $this->actingAs($this->proprietaire);
        $this->post("/livraisons/{$livraison->id}/lignes", ['quantite' => 8])->assertRedirect();

        $livraison->refresh();
        $this->assertSame(Livraison::STATUT_PARTIELLE, $livraison->statut);
        $this->assertSame(12.0, $livraison->resteALivrer());
    }

    public function test_partial_deliveries_summing_to_the_sold_quantity_mark_the_livraison_as_livree(): void
    {
        $produit = $this->creerProduitAvecStock('Farine', 800, 40);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 15,
            'montant_paye' => 12000,
            'client_nom' => 'Ibrahima Sarr',
            'livraison_lieu' => 'Entrepôt Sud',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();

        $this->actingAs($this->proprietaire);
        $this->post("/livraisons/{$livraison->id}/lignes", ['quantite' => 9])->assertRedirect();
        $this->post("/livraisons/{$livraison->id}/lignes", ['quantite' => 6])->assertRedirect();

        $livraison->refresh();
        $this->assertSame(Livraison::STATUT_LIVREE, $livraison->statut);
        $this->assertSame(0.0, $livraison->resteALivrer());
    }

    public function test_delivering_more_than_the_remainder_is_rejected(): void
    {
        $produit = $this->creerProduitAvecStock('Sucre', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 10,
            'montant_paye' => 5000,
            'client_nom' => 'Awa Diop',
            'livraison_lieu' => 'Marché central',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();

        $this->actingAs($this->proprietaire);
        $this->post("/livraisons/{$livraison->id}/lignes", ['quantite' => 11])->assertSessionHasErrors('quantite');

        $livraison->refresh();
        $this->assertSame(Livraison::STATUT_PLANIFIEE, $livraison->statut);
        $this->assertSame(10.0, $livraison->resteALivrer());
    }

    public function test_a_full_cash_sale_still_works_and_creates_no_livraison(): void
    {
        $produit = $this->creerProduitAvecStock('Savon', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 3,
            'montant_paye' => 1500,
        ])->assertRedirect();

        $this->assertSame(17.0, $produit->fresh()->stockDisponible());
        $this->assertDatabaseCount('livraisons', 0);
    }

    public function test_a_sale_with_a_debt_still_works_and_creates_no_livraison(): void
    {
        $produit = $this->creerProduitAvecStock('Huile', 2000, 10);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 3000,
            'client_nom' => 'Ibrahima Sarr',
        ])->assertRedirect();

        $vente = Vente::firstOrFail();

        $this->assertDatabaseHas('creances', ['vente_id' => $vente->id]);
        $this->assertDatabaseCount('livraisons', 0);
        $this->assertSame(Creance::STATUT_OUVERTE, Creance::firstOrFail()->statut);
    }

    public function test_a_proprietaire_can_assign_a_responsable_livreur_who_then_sees_it_in_their_list(): void
    {
        $produit = $this->creerProduitAvecStock('Ciment', 5000, 50);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 10,
            'montant_paye' => 50000,
            'client_nom' => 'Awa Diop',
            'livraison_lieu' => 'Marché central',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();
        $livreur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Livreur)->create();

        $this->actingAs($this->proprietaire);
        $this->patch("/livraisons/{$livraison->id}/responsable", ['responsable_user_id' => $livreur->id])
            ->assertRedirect();

        $this->assertSame($livreur->id, $livraison->fresh()->responsable_user_id);

        $this->actingAs($livreur);
        $this->get('/livraisons')->assertInertia(fn (Assert $page) => $page
            ->component('livraisons/index')
            ->has('livraisons', 1)
            ->where('livraisons.0.id', $livraison->id)
        );
    }

    public function test_a_non_proprietaire_cannot_assign_a_responsable_livreur(): void
    {
        $produit = $this->creerProduitAvecStock('Tôles', 8000, 30);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 40000,
            'client_nom' => 'Awa Diop',
            'livraison_lieu' => 'Quartier Nord',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();
        $livreur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Livreur)->create();

        $this->patch("/livraisons/{$livraison->id}/responsable", ['responsable_user_id' => $livreur->id])
            ->assertForbidden();

        $this->assertNull($livraison->fresh()->responsable_user_id);
    }

    public function test_assigning_a_responsable_to_an_already_delivered_livraison_is_rejected(): void
    {
        $produit = $this->creerProduitAvecStock('Sucre', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 10,
            'montant_paye' => 5000,
            'client_nom' => 'Awa Diop',
            'livraison_lieu' => 'Marché central',
        ])->assertRedirect();

        $livraison = Livraison::firstOrFail();

        $this->actingAs($this->proprietaire);
        $this->post("/livraisons/{$livraison->id}/lignes", ['quantite' => 10])->assertRedirect();
        $this->assertSame(Livraison::STATUT_LIVREE, $livraison->fresh()->statut);

        $livreur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Livreur)->create();

        $this->patch("/livraisons/{$livraison->id}/responsable", ['responsable_user_id' => $livreur->id])
            ->assertSessionHasErrors('responsable_user_id');

        $this->assertNull($livraison->fresh()->responsable_user_id);
    }
}
