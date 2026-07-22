<?php

namespace Tests\Feature\Clotures;

use App\Enums\RoleEnum;
use App\Models\Cloture;
use App\Models\Creance;
use App\Models\Entreprise;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Models\Versement;
use App\Services\ClotureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClotureTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private PointDeVente $pointDeVente;

    private User $vendeur;

    private User $caissier;

    private User $proprietaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($this->entreprise)->create();

        $this->vendeur = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Vendeur)->create();
        $this->vendeur->pointsDeVente()->attach($this->pointDeVente->id);

        $this->caissier = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Caissier)->create();
        $this->proprietaire = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Proprietaire)->create();

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

    public function test_a_cloture_validated_with_a_counted_amount_matching_the_theoretical_one_has_a_zero_ecart(): void
    {
        $produit = $this->creerProduitAvecStock('Savon', 500, 20);

        // Full cash sale: 5 * 500 = 2500 en espèces.
        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 2500,
        ])->assertRedirect();

        // Partial sale with a client: pays 1000, owes the rest (créance).
        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 4,
            'montant_paye' => 1000,
            'client_nom' => 'Awa Diop',
        ])->assertRedirect();

        $creance = Creance::firstOrFail();

        // A versement against that créance: 500 more en espèces.
        $this->post("/creances/{$creance->id}/versements", ['montant' => 500])->assertRedirect();

        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);

        $especesAttendues = $clotures->especesAttendues($this->pointDeVente);
        $this->assertSame(4000.0, $especesAttendues);

        $cloture = $clotures->valider($cloture, $especesAttendues, $this->caissier);

        $this->assertSame(Cloture::STATUT_VALIDEE, $cloture->statut);
        $this->assertSame(4000.0, (float) $cloture->especes_attendues);
        $this->assertSame(4000.0, (float) $cloture->especes_comptees);
        $this->assertSame(0.0, (float) $cloture->ecart);

        $this->assertSame(2, Paiement::whereNotNull('cloture_id')->count());
        $this->assertSame(1, Versement::whereNotNull('cloture_id')->count());
    }

    public function test_a_counted_amount_different_from_the_theoretical_one_stores_a_nonzero_ecart_that_is_never_recalculated(): void
    {
        $produit = $this->creerProduitAvecStock('Riz', 1000, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 5000,
        ])->assertRedirect();

        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);

        // 4700 counted vs 5000 theoretical: a shortfall of -300.
        $cloture = $clotures->valider($cloture, 4700, $this->caissier);

        $this->assertSame(5000.0, (float) $cloture->especes_attendues);
        $this->assertSame(4700.0, (float) $cloture->especes_comptees);
        $this->assertSame(-300.0, (float) $cloture->ecart);

        // Nothing about the domain state changes this stored écart after the
        // fact — re-reading it later still yields the exact same value.
        $ecartRelu = $cloture->fresh()->ecart;
        $this->assertSame(-300.0, (float) $ecartRelu);
    }

    public function test_a_validated_cloture_cannot_be_validated_again_directly(): void
    {
        $produit = $this->creerProduitAvecStock('Huile', 2000, 10);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 4000,
        ])->assertRedirect();

        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $cloture = $clotures->valider($cloture, 4000, $this->caissier);

        $this->expectException(ValidationException::class);
        $clotures->valider($cloture, 9999, $this->caissier);
    }

    public function test_reopening_requires_both_a_motif_and_an_authorizing_proprietaire(): void
    {
        $produit = $this->creerProduitAvecStock('Farine', 800, 15);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 3,
            'montant_paye' => 2400,
        ])->assertRedirect();

        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $cloture = $clotures->valider($cloture, 2400, $this->caissier);

        // Missing motif.
        try {
            $clotures->reouvrir($cloture, null, $this->proprietaire);
            $this->fail('Expected a ValidationException for the missing motif.');
        } catch (ValidationException) {
            // expected
        }

        // Missing (or wrong) authorizer: a caissier cannot reopen a clôture.
        try {
            $clotures->reouvrir($cloture, 'Espèces mal comptées', $this->caissier);
            $this->fail('Expected a ValidationException for the invalid authorizer.');
        } catch (ValidationException) {
            // expected
        }

        $cloture->refresh();
        $this->assertNull($cloture->motif_reouverture);
        $this->assertNull($cloture->reouverte_a);

        // Both present: reopening succeeds, the original écart is untouched.
        $ecartOriginal = (float) $cloture->ecart;
        $nouvelleCloture = $clotures->reouvrir($cloture, 'Espèces mal comptées', $this->proprietaire);

        $cloture->refresh();
        $this->assertSame('Espèces mal comptées', $cloture->motif_reouverture);
        $this->assertSame($this->proprietaire->id, $cloture->reouverte_par_user_id);
        $this->assertNotNull($cloture->reouverte_a);
        $this->assertSame(Cloture::STATUT_VALIDEE, $cloture->statut);
        $this->assertSame($ecartOriginal, (float) $cloture->ecart);

        $this->assertSame(Cloture::STATUT_OUVERTE, $nouvelleCloture->statut);
        $this->assertNull($nouvelleCloture->ecart);
        $this->assertNotSame($cloture->id, $nouvelleCloture->id);
    }

    public function test_a_paiement_already_attached_to_a_validated_cloture_is_not_counted_again_in_a_later_cloture(): void
    {
        $produit = $this->creerProduitAvecStock('Sucre', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_paye' => 1000,
        ])->assertRedirect();

        $clotures = app(ClotureService::class);
        $premiere = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $clotures->valider($premiere, 1000, $this->caissier);

        // A new, still-unattached sale after the first clôture.
        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 1,
            'montant_paye' => 500,
        ])->assertRedirect();

        $deuxieme = $clotures->ouvrir($this->pointDeVente, $this->caissier);

        // Only the new paiement counts — the first one is already rattaché.
        $this->assertSame(500.0, $clotures->especesAttendues($this->pointDeVente));

        $deuxieme = $clotures->valider($deuxieme, 500, $this->caissier);
        $this->assertSame(0.0, (float) $deuxieme->ecart);
    }
}
