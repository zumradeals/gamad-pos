<?php

namespace Tests\Feature\Stock;

use App\Models\Depot;
use App\Models\Entreprise;
use App\Models\MouvementStock;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Services\TransfertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransfertTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_transfer_is_unavailable_at_both_ends_during_transit_then_available_only_at_destination_once_received(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $depot = Depot::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 30,
        ]);

        $this->assertSame(30.0, $produit->stockDisponible($pointDeVente));
        $this->assertSame(0.0, $produit->stockDisponible($depot));

        $transferts = app(TransfertService::class);
        $entree = $transferts->initier($produit, $pointDeVente, $depot, 20);

        // In transit: gone from the source, not yet counted at destination.
        $this->assertSame(10.0, $produit->stockDisponible($pointDeVente));
        $this->assertSame(0.0, $produit->stockDisponible($depot));

        $transferts->receptionner($entree);

        $this->assertSame(10.0, $produit->stockDisponible($pointDeVente));
        $this->assertSame(20.0, $produit->stockDisponible($depot));
    }

    public function test_a_transfer_exceeding_available_stock_at_the_source_is_rejected(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $depot = Depot::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 5,
        ]);

        $transferts = app(TransfertService::class);

        $this->expectException(ValidationException::class);
        $transferts->initier($produit, $pointDeVente, $depot, 6);

        $this->assertSame(5.0, $produit->stockDisponible($pointDeVente));
    }

    public function test_receptionning_a_transfer_twice_is_rejected(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $depot = Depot::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);

        $transferts = app(TransfertService::class);
        $entree = $transferts->initier($produit, $pointDeVente, $depot, 4);
        $transferts->receptionner($entree);

        $this->expectException(ValidationException::class);
        $transferts->receptionner($entree->fresh());
    }
}
