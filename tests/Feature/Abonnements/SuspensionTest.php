<?php

namespace Tests\Feature\Abonnements;

use App\Enums\RoleEnum;
use App\Models\Abonnement;
use App\Models\Entreprise;
use App\Models\MouvementStock;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use App\Services\AbonnementService;
use App\Services\FakePaiementAbonnementProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private PointDeVente $pointDeVente;

    private User $vendeur;

    private Offre $offre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($this->entreprise)->create();
        $this->offre = Offre::factory()->create(['code' => Offre::SOLO]);

        $this->vendeur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Vendeur)->create();
        $this->vendeur->pointsDeVente()->attach($this->pointDeVente->id);

        $this->actingAs($this->vendeur);
        $this->withSession(['point_de_vente_id' => $this->pointDeVente->id]);
    }

    private function creerProduitAvecStock(string $nom, float $prixVente, float $stockInitial): Produit
    {
        $this->post('/produits', [
            'nom' => $nom,
            'prix_vente' => $prixVente,
            'unite' => 'unite',
            'quantite_initiale' => $stockInitial,
        ])->assertRedirect();

        return Produit::where('nom', $nom)->firstOrFail();
    }

    private function abonnement(string $dateEcheance): Abonnement
    {
        return Abonnement::factory()->for($this->entreprise)->for($this->offre)->create([
            'date_echeance' => $dateEcheance,
        ]);
    }

    public function test_an_abonnement_in_its_grace_period_has_full_unrestricted_access(): void
    {
        $this->abonnement(now()->subDays(3)->toDateString());
        $produit = $this->creerProduitAvecStock('Savon', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 1000,
        ])->assertRedirect();

        $this->assertDatabaseCount('ventes', 1);
    }

    public function test_a_suspended_abonnement_blocks_a_direct_call_to_a_business_write_route(): void
    {
        $this->abonnement(now()->subDays(Abonnement::JOURS_GRACE + 1)->toDateString());
        $produit = $this->creerProduitAvecStock('Riz', 1000, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 2000,
        ])->assertForbidden();

        $this->assertDatabaseCount('ventes', 0);
    }

    public function test_export_stays_reachable_and_complete_while_suspended(): void
    {
        // Data created while still valid.
        $produit = $this->creerProduitAvecStock('Huile', 2000, 10);
        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 4000,
        ])->assertRedirect();

        // Now the abonnement lapses past the grace period.
        $this->abonnement(now()->subDays(Abonnement::JOURS_GRACE + 1)->toDateString());

        $response = $this->getJson('/export')->assertOk();
        $response->assertJsonCount(1, 'produits');
        $response->assertJsonCount(1, 'ventes');
        $this->assertSame('Huile', $response->json('produits.0.nom'));
    }

    public function test_suspension_deletes_no_existing_data(): void
    {
        $produit = $this->creerProduitAvecStock('Sucre', 500, 15);
        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 3,
            'montant_paye' => 1500,
        ])->assertRedirect();

        $venteAvant = Vente::count();
        $paiementAvant = Paiement::count();
        $mouvementAvant = MouvementStock::count();

        $this->abonnement(now()->subDays(Abonnement::JOURS_GRACE + 1)->toDateString());

        $this->assertSame($venteAvant, Vente::count());
        $this->assertSame($paiementAvant, Paiement::count());
        $this->assertSame($mouvementAvant, MouvementStock::count());
        $this->assertSame(12.0, $produit->stockDisponible($this->pointDeVente));
    }

    public function test_renewing_a_suspended_abonnement_immediately_restores_write_access_without_any_manual_reset(): void
    {
        $abonnement = $this->abonnement(now()->subDays(Abonnement::JOURS_GRACE + 1)->toDateString());
        $produit = $this->creerProduitAvecStock('Farine', 800, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 1,
            'montant_paye' => 800,
        ])->assertForbidden();

        $this->assertTrue($this->entreprise->fresh()->estSuspendue());

        $provider = new FakePaiementAbonnementProvider;
        app(AbonnementService::class)->activer(
            $this->entreprise,
            $this->offre,
            $provider->confirmer(['montant' => 15000, 'reference_externe' => 'ref-renouvellement']),
        );

        $this->assertFalse($this->entreprise->fresh()->estSuspendue());
        $this->assertSame(Abonnement::STATUT_ACTIF, $abonnement->fresh()->statut);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 1,
            'montant_paye' => 800,
        ])->assertRedirect();

        $this->assertDatabaseCount('ventes', 1);
    }
}
