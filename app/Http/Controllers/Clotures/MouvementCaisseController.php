<?php

namespace App\Http\Controllers\Clotures;

use App\Http\Controllers\Controller;
use App\Models\PointDeVente;
use App\Services\ClotureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MouvementCaisseController extends Controller
{
    /**
     * Minimal endpoint (no dedicated screen) sufficient to exercise un
     * mouvement de caisse hors vente (entrée/sortie) end-to-end — reuses
     * ClotureService::enregistrerMouvementCaisse unmodified.
     */
    public function store(Request $request, ClotureService $clotures): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:entree,sortie'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'motif' => ['nullable', 'string', 'max:255'],
        ]);

        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);

        $pointDeVente = PointDeVente::findOrFail((int) $pointDeVenteId);

        $clotures->enregistrerMouvementCaisse(
            pointDeVente: $pointDeVente,
            type: $data['type'],
            montant: (float) $data['montant'],
            motif: $data['motif'] ?? null,
            createur: $request->user(),
        );

        return redirect()->back();
    }
}
