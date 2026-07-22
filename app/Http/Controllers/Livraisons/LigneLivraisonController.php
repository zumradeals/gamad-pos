<?php

namespace App\Http\Controllers\Livraisons;

use App\Http\Controllers\Controller;
use App\Models\Livraison;
use App\Services\LivraisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LigneLivraisonController extends Controller
{
    public function store(Request $request, Livraison $livraison, LivraisonService $livraisons): RedirectResponse
    {
        Gate::authorize('livrer', $livraison);

        $data = $request->validate([
            'quantite' => ['required', 'numeric', 'min:0.001'],
        ]);

        $livraisons->enregistrerLigneLivraison($livraison, (float) $data['quantite']);

        return redirect()->back();
    }
}
