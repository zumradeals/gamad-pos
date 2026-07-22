<?php

namespace Tests\Feature\Commandes;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Creance;
use App\Models\Entreprise;
use App\Models\MouvementStock;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Services\CommandeService;
use App\Services\CreanceService;
use App\Services\VenteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommandeTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private PointDeVente $pointDeVente;

    private Client $client;

    private Produit $produit;

    private User $vendeur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($this->entreprise)->create();
        $this->client = Client::factory()->for($this->pointDeVente)->create();
        $this->vendeur = User::factory()->create(['entreprise_id' => $this->entreprise->id]);

        $this->produit = Produit::factory()->for($this->entreprise)->create(['prix_vente' => 1000]);

        $this->produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $this->pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);
    }

    private function creerCommande(float $quantite, float $montantPaye): Commande
    {
        return app(CommandeService::class)->creer(
            client: $this->client,
            pointDeVente: $this->pointDeVente,
            lignes: [['produit_id' => $this->produit->id, 'quantite' => $quantite, 'prix_unitaire' => 1000]],
            montantPaye: $montantPaye,
        );
    }

    public function test_creating_a_commande_reserves_stock_making_it_unavailable_to_a_direct_vente(): void
    {
        $commande = $this->creerCommande(6, 6000);

        $this->assertSame(Commande::STATUT_EN_ATTENTE, $commande->statut);
        $this->assertSame(4.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));

        // Une vente directe qui dépasse ce qui reste (le reste étant déjà
        // amputé de la réservation) échoue, croisant VenteService.
        $this->expectException(ValidationException::class);
        app(VenteService::class)->enregistrerVente(
            vendeur: $this->vendeur,
            pointDeVente: $this->pointDeVente,
            produit: $this->produit->fresh(),
            quantite: 5,
            montantPaye: 5000,
        );
    }

    public function test_a_direct_vente_still_succeeds_within_the_stock_left_unreserved(): void
    {
        $this->creerCommande(6, 6000);

        app(VenteService::class)->enregistrerVente(
            vendeur: $this->vendeur,
            pointDeVente: $this->pointDeVente,
            produit: $this->produit->fresh(),
            quantite: 4,
            montantPaye: 4000,
        );

        $this->assertSame(0.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));
    }

    public function test_creating_a_commande_beyond_available_stock_is_rejected_and_reserves_nothing(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->creerCommande(11, 11000);
        } finally {
            $this->assertDatabaseCount('commandes', 0);
            $this->assertSame(10.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));
        }
    }

    public function test_cancelling_a_commande_releases_the_reservation_and_makes_stock_available_again(): void
    {
        $commande = $this->creerCommande(6, 6000);
        $this->assertSame(4.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));

        app(CommandeService::class)->annuler($commande);

        $this->assertSame(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
        $this->assertSame(10.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));

        $this->assertDatabaseHas('mouvements_stock', [
            'produit_id' => $this->produit->id,
            'type' => MouvementStock::TYPE_LIBERATION_RESERVATION,
            'quantite' => 6,
            'origine_type' => Commande::class,
            'origine_id' => $commande->id,
        ]);
    }

    public function test_a_commande_with_a_partial_down_payment_creates_a_creance_for_the_exact_balance(): void
    {
        $commande = $this->creerCommande(6, 2000);

        $creance = Creance::firstOrFail();

        $this->assertSame($commande->id, $creance->commande_id);
        $this->assertNull($creance->vente_id);
        $this->assertSame($this->client->id, $creance->client_id);
        $this->assertSame(4000.0, (float) $creance->montant_initial);
        $this->assertSame(Creance::STATUT_OUVERTE, $creance->statut);

        // Même mécanisme que le Chantier 4 : CreanceService règle le solde
        // sans distinction d'origine (vente ou commande).
        app(CreanceService::class)->enregistrerVersement($creance, 4000);

        $this->assertSame(0.0, $creance->fresh()->resteDu());
        $this->assertSame(Creance::STATUT_SOLDEE, $creance->fresh()->statut);
    }

    public function test_a_full_down_payment_creates_no_creance(): void
    {
        $this->creerCommande(6, 6000);

        $this->assertDatabaseCount('creances', 0);
    }

    public function test_delivering_a_commande_converts_the_reservation_into_a_definitive_stock_exit(): void
    {
        $commande = $this->creerCommande(6, 6000);

        app(CommandeService::class)->livrer($commande);

        $this->assertSame(Commande::STATUT_LIVREE, $commande->fresh()->statut);

        // Le stock reste amputé pour de bon : ni restauré, ni sur-décompté.
        $this->assertSame(4.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));

        $this->assertDatabaseHas('mouvements_stock', [
            'produit_id' => $this->produit->id,
            'type' => MouvementStock::TYPE_LIBERATION_RESERVATION,
            'quantite' => 6,
            'origine_type' => Commande::class,
            'origine_id' => $commande->id,
        ]);

        $this->assertDatabaseHas('mouvements_stock', [
            'produit_id' => $this->produit->id,
            'type' => MouvementStock::TYPE_SORTIE_VENTE,
            'quantite' => 6,
            'origine_type' => Commande::class,
            'origine_id' => $commande->id,
        ]);
    }

    public function test_a_delivered_commande_cannot_be_cancelled_nor_delivered_again(): void
    {
        $commande = $this->creerCommande(6, 6000);
        app(CommandeService::class)->livrer($commande);

        $this->expectException(ValidationException::class);
        app(CommandeService::class)->annuler($commande->fresh());
    }

    public function test_a_cancelled_commande_cannot_be_delivered(): void
    {
        $commande = $this->creerCommande(6, 6000);
        app(CommandeService::class)->annuler($commande);

        $this->expectException(ValidationException::class);
        app(CommandeService::class)->livrer($commande->fresh());
    }

    public function test_two_lines_of_the_same_produit_are_checked_against_their_combined_quantity(): void
    {
        $this->expectException(ValidationException::class);

        try {
            app(CommandeService::class)->creer(
                client: $this->client,
                pointDeVente: $this->pointDeVente,
                lignes: [
                    ['produit_id' => $this->produit->id, 'quantite' => 6, 'prix_unitaire' => 1000],
                    ['produit_id' => $this->produit->id, 'quantite' => 5, 'prix_unitaire' => 1000],
                ],
                montantPaye: 11000,
            );
        } finally {
            $this->assertSame(10.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));
            $this->assertDatabaseCount('mouvements_stock', 1); // seulement la réception du setUp
        }
    }

    public function test_a_commande_can_be_prepared_then_delivered_without_any_further_stock_effect_at_the_preparation_step(): void
    {
        $commande = $this->creerCommande(6, 6000);

        app(CommandeService::class)->preparer($commande);
        $this->assertSame(Commande::STATUT_PREPAREE, $commande->fresh()->statut);
        $this->assertSame(4.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));

        app(CommandeService::class)->livrer($commande->fresh());
        $this->assertSame(Commande::STATUT_LIVREE, $commande->fresh()->statut);
        $this->assertSame(4.0, $this->produit->fresh()->stockDisponible($this->pointDeVente));
    }
}
