<?php

namespace App\Services;

use App\Models\Inventaire;
use App\Models\LigneInventaire;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventaireService
{
    public function __construct(private readonly CorrectionStockService $corrections) {}

    public function demarrer(Model $emplacement, User $responsable, ?Carbon $date = null): Inventaire
    {
        return Inventaire::create([
            'emplacement_type' => $emplacement::class,
            'emplacement_id' => $emplacement->id,
            'date' => $date ?? now(),
            'responsable_user_id' => $responsable->id,
        ]);
    }

    /**
     * Record a counted line for a produit. The quantité théorique is read
     * from the stock engine at the moment of counting. Any non-zero écart
     * automatically proposes a stock correction — it is never applied
     * silently, it still needs to be autorisée like a manual correction.
     */
    public function enregistrerLigne(Inventaire $inventaire, Produit $produit, float $quantiteComptee): LigneInventaire
    {
        $emplacement = $inventaire->emplacement;
        $theorique = $produit->stockDisponible($emplacement);
        $ecart = round($quantiteComptee - $theorique, 3);

        return DB::transaction(function () use ($inventaire, $produit, $emplacement, $theorique, $quantiteComptee, $ecart) {
            $ligne = $inventaire->lignes()->create([
                'produit_id' => $produit->id,
                'quantite_theorique' => $theorique,
                'quantite_comptee' => $quantiteComptee,
                'ecart' => $ecart,
            ]);

            if ($ecart !== 0.0) {
                $this->corrections->proposer($produit, $emplacement, $ecart, $ligne);
            }

            return $ligne;
        });
    }
}
