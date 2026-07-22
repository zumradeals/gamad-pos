<?php

namespace App\Http\Controllers\Depenses;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Services\DepenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepenseController extends Controller
{
    /**
     * Minimal endpoint (no dedicated screen) sufficient to exercise
     * l'enregistrement d'une dépense end-to-end.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('creer', Depense::class);

        $data = $request->validate([
            'categorie' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'justificatif' => ['nullable', 'string', 'max:255'],
        ]);

        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);

        Depense::create([
            'point_de_vente_id' => (int) $pointDeVenteId,
            'user_id' => $request->user()->id,
            'statut' => Depense::STATUT_ENREGISTREE,
            ...$data,
        ]);

        return redirect()->back();
    }

    public function valider(Request $request, Depense $depense, DepenseService $depenses): RedirectResponse
    {
        Gate::authorize('valider', $depense);

        $depenses->valider($depense, $request->user());

        return redirect()->back();
    }
}
