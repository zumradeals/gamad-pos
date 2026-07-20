<?php

namespace App\Http\Controllers\Creances;

use App\Http\Controllers\Controller;
use App\Models\Creance;
use App\Services\CreanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VersementController extends Controller
{
    public function store(Request $request, Creance $creance, CreanceService $creances): RedirectResponse
    {
        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
        ]);

        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);
        abort_unless($creance->vente->point_de_vente_id === (int) $pointDeVenteId, 403);

        $creances->enregistrerVersement($creance, (float) $data['montant']);

        return redirect()->back();
    }
}
