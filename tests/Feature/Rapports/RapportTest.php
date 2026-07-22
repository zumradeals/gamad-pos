<?php

namespace Tests\Feature\Rapports;

use App\Enums\RoleEnum;
use App\Models\Client;
use App\Models\Depense;
use App\Models\Depot;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Services\AchatService;
use App\Services\ClotureService;
use App\Services\CommandeService;
use App\Services\CreanceService;
use App\Services\VenteService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RapportTest extends TestCase
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
        $this->caissier = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Caissier)->create();
        $this->proprietaire = User::factory()->pourEntreprise($this->entreprise, RoleEnum::Proprietaire)->create();
    }

    private function rapportUrl(CarbonInterface $debut, CarbonInterface $fin): string
    {
        return '/rapports?'.http_build_query([
            'point_de_vente_id' => $this->pointDeVente->id,
            'debut' => $debut->toDateString(),
            'fin' => $fin->toDateString(),
        ]);
    }

    public function test_recettes_matches_what_a_cloture_would_have_calculated_for_its_vente_scope_and_also_counts_commande_down_payments(): void
    {
        $debut = now()->subMinute();

        $produit = Produit::factory()->for($this->entreprise)->create(['prix_vente' => 1000]);
        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $this->pointDeVente->id,
            'type' => \App\Models\MouvementStock::TYPE_RECEPTION,
            'quantite' => 20,
        ]);

        $clotures = app(ClotureService::class);
        $cloture = $clotures->ouvrir($this->pointDeVente, $this->caissier);

        // Vente comptant : 3 * 1000 = 3000 en espèces.
        app(VenteService::class)->enregistrerVente(
            vendeur: $this->vendeur,
            pointDeVente: $this->pointDeVente,
            produit: $produit->fresh(),
            quantite: 3,
            montantPaye: 3000,
        );

        // Vente à crédit partiel : paie 800 sur 2000, créance de 1200 réglée ensuite intégralement.
        $vente2 = app(VenteService::class)->enregistrerVente(
            vendeur: $this->vendeur,
            pointDeVente: $this->pointDeVente,
            produit: $produit->fresh(),
            quantite: 2,
            montantPaye: 800,
            client: ['nom' => 'Awa Diop', 'telephone' => null],
        );
        app(CreanceService::class)->enregistrerVersement($vente2->creance, 1200);

        // Mouvements de caisse hors vente : entrée 500, sortie 200 (net +300).
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, \App\Models\MouvementCaisse::TYPE_ENTREE, 500, 'Apport', $this->caissier);
        $clotures->enregistrerMouvementCaisse($this->pointDeVente, \App\Models\MouvementCaisse::TYPE_SORTIE, 200, 'Retrait', $this->caissier);

        $especesAttendues = $clotures->especesAttendues($this->pointDeVente);
        $cloture = $clotures->valider($cloture, $especesAttendues, $this->caissier);

        // Le "vrai" périmètre vente de la clôture : 3000 + 800 + 1200 + (500-200) = 5300.
        $this->assertSame(5300.0, (float) $cloture->fresh()->especes_attendues);

        // Une commande avec acompte partiel, en plus, hors du périmètre de la clôture actuelle.
        $client = Client::factory()->for($this->pointDeVente)->create();
        app(CommandeService::class)->creer(
            client: $client,
            pointDeVente: $this->pointDeVente,
            lignes: [['produit_id' => $produit->id, 'quantite' => 1, 'prix_unitaire' => 1000]],
            montantPaye: 400,
        );

        $fin = now()->addMinute();

        $rapport = $this->rapportPourProprietaire($debut, $fin);

        // 3000 (vente comptant) + 800 (acompte vente à crédit) + 1200 (versement) + 400 (acompte commande) = 5400.
        // Les mouvements de caisse hors vente (+300) n'entrent PAS dans les recettes.
        $this->assertSame(5400.0, (float) $rapport['recettes']);

        // Cohérence avec la clôture : recettes moins l'acompte de commande (que la
        // clôture ne couvre pas encore, cf. Catalogue des invariants) doit
        // retomber exactement sur especes_attendues moins le net de caisse hors
        // vente — même donnée, même filtre, juste bornée par période plutôt que
        // par clôture.
        $this->assertSame(5000.0, (float) $rapport['recettes'] - 400);
        $this->assertSame(5000.0, (float) $cloture->fresh()->especes_attendues - 300);
    }

    public function test_depenses_and_achats_are_aggregated_correctly_over_the_period(): void
    {
        $debut = now()->subMinute();

        Depense::create([
            'point_de_vente_id' => $this->pointDeVente->id,
            'user_id' => $this->caissier->id,
            'categorie' => 'Électricité',
            'montant' => 300,
            'statut' => Depense::STATUT_VALIDEE,
        ]);

        $fournisseur = Fournisseur::factory()->create(['entreprise_id' => $this->entreprise->id]);
        $depot = Depot::factory()->for($this->entreprise)->create();
        $produit = Produit::factory()->for($this->entreprise)->create();

        app(AchatService::class)->enregistrerAchat(
            createur: $this->proprietaire,
            fournisseur: $fournisseur,
            emplacement: $depot,
            lignes: [['produit_id' => $produit->id, 'quantite' => 10, 'prix_unitaire' => 500]],
            montantPaye: 5000,
        );

        $fin = now()->addMinute();

        $rapport = $this->rapportPourProprietaire($debut, $fin);

        $this->assertSame(300.0, (float) $rapport['depenses']);
        $this->assertSame(5000.0, (float) $rapport['achats']);
    }

    public function test_benefice_estime_follows_the_documented_formula(): void
    {
        $debut = now()->subMinute();

        $produit = Produit::factory()->for($this->entreprise)->create(['prix_vente' => 1000]);
        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $this->pointDeVente->id,
            'type' => \App\Models\MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);

        app(VenteService::class)->enregistrerVente(
            vendeur: $this->vendeur,
            pointDeVente: $this->pointDeVente,
            produit: $produit->fresh(),
            quantite: 5,
            montantPaye: 5000,
        );

        Depense::create([
            'point_de_vente_id' => $this->pointDeVente->id,
            'user_id' => $this->caissier->id,
            'categorie' => 'Transport',
            'montant' => 500,
            'statut' => Depense::STATUT_VALIDEE,
        ]);

        $fournisseur = Fournisseur::factory()->create(['entreprise_id' => $this->entreprise->id]);
        $depot = Depot::factory()->for($this->entreprise)->create();

        app(AchatService::class)->enregistrerAchat(
            createur: $this->proprietaire,
            fournisseur: $fournisseur,
            emplacement: $depot,
            lignes: [['produit_id' => $produit->id, 'quantite' => 4, 'prix_unitaire' => 300]],
            montantPaye: 1200,
        );

        $fin = now()->addMinute();

        $rapport = $this->rapportPourProprietaire($debut, $fin);

        $this->assertSame(5000.0, (float) $rapport['recettes']);
        $this->assertSame(500.0, (float) $rapport['depenses']);
        $this->assertSame(1200.0, (float) $rapport['achats']);
        $this->assertSame(3300.0, (float) $rapport['benefice_estime']); // 5000 - 500 - 1200
    }

    public function test_marge_uses_the_produits_current_prix_achat_and_documents_the_limitation(): void
    {
        $debut = now()->subMinute();

        $produit = Produit::factory()->for($this->entreprise)->create(['prix_vente' => 1000, 'prix_achat' => 600]);
        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $this->pointDeVente->id,
            'type' => \App\Models\MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);

        app(VenteService::class)->enregistrerVente(
            vendeur: $this->vendeur,
            pointDeVente: $this->pointDeVente,
            produit: $produit->fresh(),
            quantite: 5,
            montantPaye: 5000,
        );

        // Le prix d'achat change APRÈS la vente : la marge doit refléter ce
        // prix courant (700), pas le prix (600) réellement en vigueur au
        // moment de la vente — c'est exactement la limite documentée.
        $produit->update(['prix_achat' => 700]);

        $fin = now()->addMinute();

        $rapport = $this->rapportPourProprietaire($debut, $fin);

        $this->assertSame(1500.0, (float) $rapport['marge']['montant']); // (1000 - 700) * 5
        $this->assertStringContainsString('prix d\'achat', $rapport['marge']['avertissement']);
    }

    public function test_etat_caisse_reflects_the_currently_open_cloture_or_is_null_without_one(): void
    {
        $debut = now()->subMinute();
        $fin = now()->addMinute();

        $rapportSansCloture = $this->rapportPourProprietaire($debut, $fin);
        $this->assertFalse($rapportSansCloture['etat_caisse']['cloture_ouverte']);
        $this->assertNull($rapportSansCloture['etat_caisse']['especes_attendues']);

        app(ClotureService::class)->ouvrir($this->pointDeVente, $this->caissier, 1000);

        $rapportAvecCloture = $this->rapportPourProprietaire($debut, now()->addMinute());
        $this->assertTrue($rapportAvecCloture['etat_caisse']['cloture_ouverte']);
        $this->assertSame(1000.0, (float) $rapportAvecCloture['etat_caisse']['especes_attendues']);
    }

    public function test_a_non_proprietaire_cannot_access_the_rapport_endpoint(): void
    {
        $debut = now()->subMinute();
        $fin = now()->addMinute();

        $this->actingAs($this->vendeur)
            ->get($this->rapportUrl($debut, $fin))
            ->assertForbidden();

        $this->actingAs($this->caissier)
            ->get($this->rapportUrl($debut, $fin))
            ->assertForbidden();
    }

    public function test_a_proprietaire_of_another_entreprise_cannot_access_the_rapport(): void
    {
        $entrepriseEtrangere = Entreprise::factory()->create();
        $proprietaireEtranger = User::factory()->pourEntreprise($entrepriseEtrangere, RoleEnum::Proprietaire)->create();

        $debut = now()->subMinute();
        $fin = now()->addMinute();

        $this->actingAs($proprietaireEtranger)
            ->get($this->rapportUrl($debut, $fin))
            ->assertForbidden();
    }

    public function test_a_proprietaire_can_access_the_rapport_of_their_own_point_de_vente(): void
    {
        $debut = now()->subMinute();
        $fin = now()->addMinute();

        $this->actingAs($this->proprietaire)
            ->get($this->rapportUrl($debut, $fin))
            ->assertOk()
            ->assertJsonStructure([
                'point_de_vente_id',
                'periode' => ['debut', 'fin'],
                'recettes',
                'depenses',
                'achats',
                'marge' => ['montant', 'avertissement'],
                'benefice_estime',
                'etat_caisse' => ['cloture_ouverte', 'cloture_id', 'especes_attendues'],
            ]);
    }

    private function rapportPourProprietaire(CarbonInterface $debut, CarbonInterface $fin): array
    {
        return $this->actingAs($this->proprietaire)
            ->get($this->rapportUrl($debut, $fin))
            ->assertOk()
            ->json();
    }
}
