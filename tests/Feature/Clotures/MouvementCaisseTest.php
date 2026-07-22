<?php

namespace Tests\Feature\Clotures;

use App\Enums\RoleEnum;
use App\Models\Cloture;
use App\Models\Entreprise;
use App\Models\MouvementCaisse;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Services\ClotureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MouvementCaisseTest extends TestCase
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

    public function test_a_fonds_initial_is_included_in_especes_attendues_from_the_start(): void
    {
        $clotures = app(ClotureService::class);
        $clotures->ouvrir($this->pointDeVente, $this->caissier, 10000);

        $this->assertSame(10000.0, $clotures->especesAttendues($this->pointDeVente));

        $this->assertDatabaseHas('mouvements_caisse', [
            'point_de_vente_id' => $this->pointDeVente->id,
            'type' => MouvementCaisse::TYPE_FONDS_INITIAL,
            'montant' => 10000,
        ]);
    }

    public function test_entrees_and_sorties_are_correctly_reflected_in_especes_attendues_at_validation(): void
    {
        $produit = $this->creerProduitAvecStock('Savon', 500, 20);

        $this->post('/ventes', [
            'produit_id' => $produit->id,
            'quantite' => 5,
            'montant_paye' => 2500,
        ])->assertRedirect();

        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier, 5000);

        // Dépôt bancaire en cours de journée (sortie de caisse).
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, MouvementCaisse::TYPE_SORTIE, 3000, 'Dépôt bancaire', $this->caissier);

        // Apport supplémentaire du propriétaire (entrée de caisse).
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, MouvementCaisse::TYPE_ENTREE, 1000, 'Apport', $this->proprietaire);

        // 5000 (fonds initial) + 2500 (vente) - 3000 (sortie) + 1000 (entrée) = 5500.
        $especesAttendues = $clotures->especesAttendues($this->pointDeVente);
        $this->assertSame(5500.0, $especesAttendues);

        $cloture = $clotures->valider($cloture, $especesAttendues, $this->caissier);

        $this->assertSame(5500.0, (float) $cloture->especes_attendues);
        $this->assertSame(0.0, (float) $cloture->ecart);
    }

    public function test_a_mouvement_de_caisse_without_an_open_cloture_is_rejected(): void
    {
        $clotures = app(ClotureService::class);

        $this->expectException(ValidationException::class);
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, MouvementCaisse::TYPE_ENTREE, 500, 'Apport', $this->caissier);
    }

    public function test_a_mouvement_de_caisse_is_rejected_once_the_cloture_is_validated(): void
    {
        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);
        $clotures->valider($cloture, 0, $this->caissier);

        $this->expectException(ValidationException::class);
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, MouvementCaisse::TYPE_ENTREE, 500, 'Apport', $this->caissier);
    }

    public function test_a_vendeur_cannot_register_a_mouvement_de_caisse(): void
    {
        $clotures = app(ClotureService::class);
        $clotures->ouvrir($this->pointDeVente, $this->caissier);

        $this->expectException(ValidationException::class);
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, MouvementCaisse::TYPE_ENTREE, 500, 'Apport', $this->vendeur);
    }

    public function test_registering_a_mouvement_de_caisse_via_http_attaches_it_to_the_open_cloture(): void
    {
        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);

        $this->actingAs($this->caissier)
            ->withSession(['point_de_vente_id' => $this->pointDeVente->id])
            ->post('/mouvements-caisse', [
                'type' => 'sortie',
                'montant' => 750,
                'motif' => 'Retrait propriétaire',
            ])->assertRedirect();

        $mouvement = MouvementCaisse::where('type', MouvementCaisse::TYPE_SORTIE)->firstOrFail();
        $this->assertSame($cloture->id, $mouvement->cloture_id);
        $this->assertSame(750.0, (float) $mouvement->montant);
        $this->assertSame(-750.0, $clotures->especesAttendues($this->pointDeVente));
    }

    public function test_a_second_cloture_does_not_recount_the_previous_ones_mouvements_de_caisse(): void
    {
        $clotures = app(ClotureService::class);

        $premiere = $clotures->ouvrir($this->pointDeVente, $this->caissier, 2000);
        $premiere = $clotures->valider($premiere, 2000, $this->caissier);
        $this->assertSame(2000.0, (float) $premiere->especes_attendues);

        $deuxieme = $clotures->ouvrir($this->pointDeVente, $this->caissier, 500);
        $this->assertSame(500.0, $clotures->especesAttendues($this->pointDeVente));

        $deuxieme = $clotures->valider($deuxieme, 500, $this->caissier);
        $this->assertSame(500.0, (float) $deuxieme->especes_attendues);
        $this->assertSame(0.0, (float) $deuxieme->ecart);
    }
}
