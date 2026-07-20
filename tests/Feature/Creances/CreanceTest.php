<?php

namespace Tests\Feature\Creances;

use App\Enums\RoleEnum;
use App\Models\Creance;
use App\Models\Entreprise;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreanceTest extends TestCase
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

    public function test_a_partial_payment_with_a_client_creates_a_debt_for_the_exact_balance(): void
    {
        $produit = $this->creerProduitAvecStock('Sac de riz', 1000, 10);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 4,
            'montant_paye' => 1500,
            'client_nom' => 'Awa Diop',
            'client_telephone' => '770000000',
        ])->assertRedirect();

        $vente = Vente::firstOrFail();
        $creance = Creance::firstOrFail();

        $this->assertSame($vente->id, $creance->vente_id);
        $this->assertSame('Awa Diop', $creance->client->nom);
        $this->assertSame(2500.0, (float) $creance->montant_initial);
        $this->assertSame(Creance::STATUT_OUVERTE, $creance->statut);
    }

    public function test_a_partial_payment_without_a_client_is_rejected_and_creates_nothing(): void
    {
        $produit = $this->creerProduitAvecStock('Huile', 2000, 10);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 3000,
        ])->assertSessionHasErrors('client');

        $this->assertDatabaseCount('ventes', 0);
        $this->assertDatabaseCount('creances', 0);
        $this->assertSame(10.0, $produit->fresh()->stockDisponible());
    }

    public function test_a_versement_that_brings_the_balance_to_zero_settles_the_debt(): void
    {
        $produit = $this->creerProduitAvecStock('Sucre', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 10,
            'montant_paye' => 2000,
            'client_nom' => 'Modou Fall',
        ])->assertRedirect();

        $creance = Creance::firstOrFail();
        $this->assertSame(3000.0, $creance->resteDu());

        $this->post("/creances/{$creance->id}/versements", [
            'montant' => 3000,
        ])->assertRedirect();

        $creance->refresh();
        $this->assertSame(0.0, $creance->resteDu());
        $this->assertSame(Creance::STATUT_SOLDEE, $creance->statut);
    }

    public function test_a_versement_exceeding_the_balance_is_rejected_and_leaves_it_unchanged(): void
    {
        $produit = $this->creerProduitAvecStock('Farine', 800, 15);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 1000,
            'client_nom' => 'Ibrahima Sarr',
        ])->assertRedirect();

        $creance = Creance::firstOrFail();
        $resteAvant = $creance->resteDu();

        $this->post("/creances/{$creance->id}/versements", [
            'montant' => $resteAvant + 1,
        ])->assertSessionHasErrors('montant');

        $creance->refresh();
        $this->assertSame($resteAvant, $creance->resteDu());
        $this->assertSame(Creance::STATUT_OUVERTE, $creance->statut);
    }

    public function test_a_full_cash_sale_still_works_and_creates_no_debt(): void
    {
        $produit = $this->creerProduitAvecStock('Savon', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 3,
            'montant_paye' => 1500,
        ])->assertRedirect();

        $this->assertSame(17.0, $produit->fresh()->stockDisponible());
        $this->assertDatabaseCount('creances', 0);
        $this->assertDatabaseCount('clients', 0);
    }
}
