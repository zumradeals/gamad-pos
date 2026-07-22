<?php

namespace Tests\Feature\Stock;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\MouvementStock;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Services\CorrectionStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorrectionStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_correction_without_a_motif_and_without_an_authorizing_user_is_rejected_and_creates_nothing(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);

        $corrections = app(CorrectionStockService::class);

        try {
            $corrections->creerManuelle($produit, $pointDeVente, -2, null, null);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertDatabaseCount('corrections_stock', 0);
        $this->assertDatabaseMissing('mouvements_stock', ['type' => MouvementStock::TYPE_CORRECTION]);
        $this->assertSame(10.0, $produit->stockDisponible($pointDeVente));
    }

    public function test_a_correction_with_a_motif_but_no_authorized_proprietaire_is_rejected(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();
        $vendeur = User::factory()->pourEntreprise($entreprise, RoleEnum::Vendeur)->create();

        $corrections = app(CorrectionStockService::class);

        $this->expectException(ValidationException::class);
        $corrections->creerManuelle($produit, $pointDeVente, -2, 'Casse constatée', $vendeur);
    }

    public function test_an_authorized_correction_adjusts_available_stock_by_the_signed_ecart(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();
        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 10,
        ]);

        $corrections = app(CorrectionStockService::class);
        $correction = $corrections->creerManuelle($produit, $pointDeVente, -3, 'Casse constatée', $proprietaire);

        $this->assertSame(7.0, $produit->stockDisponible($pointDeVente));
        $this->assertSame('appliquee', $correction->statut);
        $this->assertSame($proprietaire->id, $correction->autorise_par_user_id);
    }
}
