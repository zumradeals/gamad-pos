<?php

namespace App\Http\Controllers\PointsDeVente;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SelectionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('points-de-vente/selection', [
            'pointsDeVente' => $request->user()->pointsDeVente()->get(['points_de_vente.id', 'nom', 'adresse']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'point_de_vente_id' => ['required', 'integer'],
        ]);

        $pointDeVente = $request->user()->pointsDeVente()->findOrFail($data['point_de_vente_id']);

        $request->session()->put('point_de_vente_id', $pointDeVente->id);

        return redirect()->route('home');
    }
}
