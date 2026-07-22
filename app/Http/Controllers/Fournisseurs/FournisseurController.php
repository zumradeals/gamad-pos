<?php

namespace App\Http\Controllers\Fournisseurs;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FournisseurController extends Controller
{
    /**
     * Minimal endpoint (no dedicated screen) — condition préalable au
     * chantier Achats, rien de plus.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $fournisseurs = Fournisseur::where('entreprise_id', $user->entreprise_id)
            ->get()
            ->filter(fn (Fournisseur $fournisseur) => Gate::forUser($user)->allows('voir', $fournisseur))
            ->values();

        return response()->json(['fournisseurs' => $fournisseurs]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('creer', Fournisseur::class);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'conditions_commerciales' => ['nullable', 'string'],
            'delais_habituels' => ['nullable', 'string'],
        ]);

        Fournisseur::create([
            'entreprise_id' => $request->user()->entreprise_id,
            ...$data,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, Fournisseur $fournisseur): RedirectResponse
    {
        Gate::authorize('modifier', $fournisseur);

        $data = $request->validate([
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'conditions_commerciales' => ['nullable', 'string'],
            'delais_habituels' => ['nullable', 'string'],
        ]);

        $fournisseur->update($data);

        return redirect()->back();
    }
}
