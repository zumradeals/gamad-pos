<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Services\CorrectionStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CorrectionStockController extends Controller
{
    /**
     * Minimal endpoint (no dedicated screen) sufficient to exercise a
     * correction de stock end-to-end — reuses CorrectionStockService from
     * Chantier 6 unmodified, scoped to the current point de vente as
     * emplacement.
     */
    public function store(Request $request, CorrectionStockService $corrections): RedirectResponse
    {
        $data = $request->validate([
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'ecart' => ['required', 'numeric'],
            'motif' => ['required', 'string', 'max:255'],
        ]);

        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);

        $pointDeVente = PointDeVente::findOrFail((int) $pointDeVenteId);
        $produit = Produit::findOrFail((int) $data['produit_id']);

        $corrections->creerManuelle($produit, $pointDeVente, (float) $data['ecart'], $data['motif'], $request->user());

        return redirect()->back();
    }
}
