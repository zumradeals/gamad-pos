<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Cloture;
use App\Models\Creance;
use App\Models\Livraison;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Raw JSON, no dedicated screen — the whole point is that it stays
     * reachable even while suspended (never gated by
     * EmpecherEcritureSiAbonnementSuspendu): a suspension restricts new
     * writes, it never hides or deletes existing data.
     */
    public function index(Request $request): JsonResponse
    {
        $entreprise = $request->user()->entreprise;

        abort_unless($entreprise, 403);

        return response()->json([
            'produits' => Produit::where('entreprise_id', $entreprise->id)->get(),
            'ventes' => Vente::whereHas('pointDeVente', fn ($q) => $q->where('entreprise_id', $entreprise->id))
                ->with(['lignes', 'paiements'])
                ->get(),
            'creances' => Creance::whereHas('vente.pointDeVente', fn ($q) => $q->where('entreprise_id', $entreprise->id))
                ->with('versements')
                ->get(),
            'livraisons' => Livraison::whereHas('vente.pointDeVente', fn ($q) => $q->where('entreprise_id', $entreprise->id))
                ->with('lignesLivraison')
                ->get(),
            'clotures' => Cloture::whereHas('pointDeVente', fn ($q) => $q->where('entreprise_id', $entreprise->id))
                ->get(),
        ]);
    }
}
