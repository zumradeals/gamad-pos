<?php

namespace Tests\Feature\Stock;

use App\Models\PointDeVente;
use App\Models\Produit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockMigrationTest extends TestCase
{
    /**
     * This test drives the migrator directly (rollback, then re-migrate) to
     * simulate the pre-Chantier-6 schema, so it manages its own database
     * lifecycle with real migrate:fresh calls rather than
     * RefreshDatabase's transactional reset — mixing the two would nest a
     * nested transaction around the very DDL this test needs to observe.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh');

        parent::tearDown();
    }

    /**
     * The "avant/après" proof required before touching existing data: a
     * produit and its stock movement, inserted in the exact shape they had
     * before Chantier 6 (point_de_vente_id on both tables, no entreprise_id,
     * no emplacement columns), must come out the other side of the
     * emplacement migration with a strictly identical stock in their point
     * de vente d'origine.
     *
     * RefreshDatabase has already migrated the schema all the way to its
     * final Chantier 6 shape for this test class. We roll back exactly the
     * two migrations that alter produits and mouvements_stock, to recreate
     * the pre-Chantier-6 schema those tables actually had in Chantiers 3-5,
     * insert data the old way, then re-apply those two migrations so the
     * real backfill logic runs against it.
     */
    public function test_a_produit_created_before_this_chantier_keeps_an_identical_stock_in_its_origin_point_de_vente_after_migrating(): void
    {
        // Roll back everything from (and including) the produits
        // entreprise_id migration onward, however many migrations that now
        // is — later chantiers keep adding migration files after it, so the
        // step count is derived from the migrations directory rather than
        // hardcoded.
        $migrations = collect(glob(database_path('migrations/*.php')))
            ->map(fn (string $path) => basename($path, '.php'))
            ->sort()
            ->values();

        $depart = $migrations->search(fn (string $nom) => str_starts_with($nom, '2026_07_22_100005'));
        $step = $migrations->count() - $depart;

        Artisan::call('migrate:rollback', ['--step' => $step]);

        $this->assertTrue(Schema::hasColumn('produits', 'point_de_vente_id'));
        $this->assertFalse(Schema::hasColumn('produits', 'entreprise_id'));
        $this->assertTrue(Schema::hasColumn('mouvements_stock', 'point_de_vente_id'));
        $this->assertFalse(Schema::hasColumn('mouvements_stock', 'emplacement_type'));

        $entrepriseId = DB::table('entreprises')->insertGetId([
            'nom' => 'Quincaillerie Diop',
            'secteur_activite' => 'Quincaillerie',
            'devise' => 'XOF',
            'pays' => 'SN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pointDeVenteId = DB::table('points_de_vente')->insertGetId([
            'entreprise_id' => $entrepriseId,
            'nom' => 'Boutique historique',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $produitId = DB::table('produits')->insertGetId([
            'point_de_vente_id' => $pointDeVenteId,
            'nom' => 'Ciment (produit historique)',
            'prix_vente' => 5000,
            'unite' => 'sac',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mouvements_stock')->insert([
            'produit_id' => $produitId,
            'point_de_vente_id' => $pointDeVenteId,
            'type' => 'reception',
            'quantite' => 42,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mouvements_stock')->insert([
            'produit_id' => $produitId,
            'point_de_vente_id' => $pointDeVenteId,
            'type' => 'sortie_vente',
            'quantite' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Re-apply the Chantier 6 migrations: this runs the real backfill
        // against the legacy-shaped rows inserted above.
        Artisan::call('migrate');

        $produit = Produit::findOrFail($produitId);
        $pointDeVente = PointDeVente::findOrFail($pointDeVenteId);

        $this->assertSame($entrepriseId, $produit->entreprise_id);
        $this->assertSame(33.0, $produit->stockDisponible($pointDeVente));
    }
}
