<?php

namespace App\Http\Controllers\Achats;

use App\Http\Controllers\Controller;
use App\Models\DetteFournisseur;
use App\Services\DetteFournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VersementFournisseurController extends Controller
{
    public function store(Request $request, DetteFournisseur $detteFournisseur, DetteFournisseurService $dettes): RedirectResponse
    {
        Gate::authorize('verser', $detteFournisseur);

        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
        ]);

        $dettes->enregistrerVersement($detteFournisseur, (float) $data['montant']);

        return redirect()->back();
    }
}
