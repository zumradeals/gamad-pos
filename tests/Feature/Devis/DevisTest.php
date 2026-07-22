<?php

namespace Tests\Feature\Devis;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Devis;
use App\Models\Entreprise;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Services\CommandeService;
use App\Services\DevisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DevisTest extends TestCase
{
    use RefreshDatabase;

    private PointDeVente $pointDeVente;

    private Client $client;

    private Produit $sac;

    private Produit $bidon;

    protected function setUp(): void
    {
        parent::setUp();

        $entreprise = Entreprise::factory()->create();
        $this->pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $this->client = Client::factory()->for($this->pointDeVente)->create();

        $this->sac = Produit::factory()->for($entreprise)->create(['prix_vente' => 1000]);
        $this->bidon = Produit::factory()->for($entreprise)->create(['prix_vente' => 2500]);

        $this->sac->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $this->pointDeVente->id,
            'type' => \App\Models\MouvementStock::TYPE_RECEPTION,
            'quantite' => 20,
        ]);

        $this->bidon->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $this->pointDeVente->id,
            'type' => \App\Models\MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);
    }

    private function lignesExemple(): array
    {
        return [
            ['produit_id' => $this->sac->id, 'quantite' => 5, 'prix_unitaire' => 1000],
            ['produit_id' => $this->bidon->id, 'quantite' => 2, 'prix_unitaire' => 2500],
        ];
    }

    public function test_proposing_a_devis_computes_its_total_from_its_lines_and_touches_neither_stock_nor_caisse(): void
    {
        $devis = app(DevisService::class)->proposer($this->client, $this->pointDeVente, $this->lignesExemple());

        $this->assertSame(10000.0, (float) $devis->montant_total);
        $this->assertSame(Devis::STATUT_PROPOSE, $devis->statut);
        $this->assertCount(2, $devis->lignes);

        // Aucun effet de bord : ni stock, ni caisse, ni créance, ni commande.
        $this->assertSame(20.0, $this->sac->fresh()->stockDisponible($this->pointDeVente));
        $this->assertSame(10.0, $this->bidon->fresh()->stockDisponible($this->pointDeVente));
        $this->assertDatabaseCount('mouvements_stock', 2); // seulement les deux réceptions du setUp
        $this->assertDatabaseCount('paiements', 0);
        $this->assertDatabaseCount('mouvements_caisse', 0);
        $this->assertDatabaseCount('creances', 0);
        $this->assertDatabaseCount('commandes', 0);
    }

    public function test_accepter_refuser_et_expirer_ne_sont_possibles_que_depuis_propose(): void
    {
        $devis = app(DevisService::class);

        $accepte = $devis->proposer($this->client, $this->pointDeVente, $this->lignesExemple());
        $devis->accepter($accepte);
        $this->assertSame(Devis::STATUT_ACCEPTE, $accepte->fresh()->statut);

        $this->expectException(ValidationException::class);
        $devis->accepter($accepte->fresh());
    }

    public function test_un_devis_refuse_reste_refuse(): void
    {
        $service = app(DevisService::class);

        $devis = $service->proposer($this->client, $this->pointDeVente, $this->lignesExemple());
        $service->refuser($devis);

        $this->assertSame(Devis::STATUT_REFUSE, $devis->fresh()->statut);
    }

    public function test_un_devis_peut_etre_marque_expire_explicitement(): void
    {
        $service = app(DevisService::class);

        $devis = $service->proposer($this->client, $this->pointDeVente, $this->lignesExemple());
        $service->expirer($devis);

        $this->assertSame(Devis::STATUT_EXPIRE, $devis->fresh()->statut);
    }

    public function test_transforming_a_devis_that_is_not_accepted_is_rejected(): void
    {
        $service = app(DevisService::class);
        $devis = $service->proposer($this->client, $this->pointDeVente, $this->lignesExemple());

        $this->expectException(ValidationException::class);
        $service->transformerEnCommande($devis, 10000);
    }

    public function test_an_accepted_devis_transforms_into_a_commande_copying_its_lines_and_reserves_stock_only_then(): void
    {
        $service = app(DevisService::class);
        $devis = $service->proposer($this->client, $this->pointDeVente, $this->lignesExemple());
        $service->accepter($devis);

        // Toujours aucun effet sur le stock au stade "accepté".
        $this->assertSame(20.0, $this->sac->fresh()->stockDisponible($this->pointDeVente));

        $commande = $service->transformerEnCommande($devis->fresh(), 10000);

        $this->assertInstanceOf(Commande::class, $commande);
        $this->assertSame($devis->id, $commande->devis_id);
        $this->assertSame(10000.0, (float) $commande->montant_total);
        $this->assertCount(2, $commande->lignes);
        $this->assertSame($this->client->id, $commande->client_id);

        // La réservation n'intervient qu'à la création de la commande.
        $this->assertSame(15.0, $this->sac->fresh()->stockDisponible($this->pointDeVente));
        $this->assertSame(8.0, $this->bidon->fresh()->stockDisponible($this->pointDeVente));
    }

    public function test_the_same_devis_cannot_be_transformed_into_a_commande_twice(): void
    {
        $service = app(DevisService::class);
        $devis = $service->proposer($this->client, $this->pointDeVente, $this->lignesExemple());
        $service->accepter($devis);
        $service->transformerEnCommande($devis->fresh(), 10000);

        $this->expectException(ValidationException::class);
        $service->transformerEnCommande($devis->fresh(), 10000);
    }
}
