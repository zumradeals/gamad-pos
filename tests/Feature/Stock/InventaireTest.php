<?php

namespace Tests\Feature\Stock;

use App\Enums\RoleEnum;
use App\Models\CorrectionStock;
use App\Models\Entreprise;
use App\Models\MouvementStock;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Models\User;
use App\Services\CorrectionStockService;
use App\Services\InventaireService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_nonzero_ecart_proposes_a_correction_without_applying_it_to_stock(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();
        $magasinier = User::factory()->pourEntreprise($entreprise, RoleEnum::Magasinier)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 20,
        ]);

        $inventaires = app(InventaireService::class);
        $inventaire = $inventaires->demarrer($pointDeVente, $magasinier);
        $ligne = $inventaires->enregistrerLigne($inventaire, $produit, 17);

        $this->assertSame(20.0, (float) $ligne->quantite_theorique);
        $this->assertSame(17.0, (float) $ligne->quantite_comptee);
        $this->assertSame(-3.0, (float) $ligne->ecart);

        $correction = $ligne->correctionStock;
        $this->assertNotNull($correction);
        $this->assertSame(CorrectionStock::STATUT_PROPOSEE, $correction->statut);

        // The proposed correction has not moved stock: still 20, not 17.
        $this->assertSame(20.0, $produit->stockDisponible($pointDeVente));

        $proprietaire = User::factory()->pourEntreprise($entreprise, RoleEnum::Proprietaire)->create();
        app(CorrectionStockService::class)->autoriser($correction, "Écart constaté à l'inventaire", $proprietaire);

        $this->assertSame(17.0, $produit->stockDisponible($pointDeVente));
        $this->assertSame(CorrectionStock::STATUT_APPLIQUEE, $correction->fresh()->statut);
    }

    public function test_a_zero_ecart_line_proposes_no_correction(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointDeVente = PointDeVente::factory()->for($entreprise)->create();
        $produit = Produit::factory()->for($entreprise)->create();
        $magasinier = User::factory()->pourEntreprise($entreprise, RoleEnum::Magasinier)->create();

        $produit->mouvementsStock()->create([
            'emplacement_type' => PointDeVente::class,
            'emplacement_id' => $pointDeVente->id,
            'type' => MouvementStock::TYPE_RECEPTION,
            'quantite' => 20,
        ]);

        $inventaires = app(InventaireService::class);
        $inventaire = $inventaires->demarrer($pointDeVente, $magasinier);
        $ligne = $inventaires->enregistrerLigne($inventaire, $produit, 20);

        $this->assertNull($ligne->correctionStock);
        $this->assertDatabaseCount('corrections_stock', 0);
    }
}
