<?php

namespace App\Http\Controllers\Livraisons;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Livraison;
use App\Models\User;
use App\Services\LivraisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LivraisonController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $estProprietaire = $user->role === RoleEnum::Proprietaire;

        $livraisons = $estProprietaire
            ? Livraison::query()
                ->whereHas('vente.pointDeVente', fn ($query) => $query->where('entreprise_id', $user->entreprise_id))
                ->with(['client:id,nom', 'responsable:id,name'])
                ->get()
            : $user->livraisons()->with(['client:id,nom', 'responsable:id,name'])->get();

        return Inertia::render('livraisons/index', [
            'livraisons' => $livraisons->map(fn (Livraison $livraison) => [
                'id' => $livraison->id,
                'lieu' => $livraison->lieu,
                'statut' => $livraison->statut,
                'client' => $livraison->client->nom,
                'reste_a_livrer' => $livraison->resteALivrer(),
                'responsable_id' => $livraison->responsable_user_id,
                'responsable_nom' => $livraison->responsable?->name,
            ]),
            'livreurs' => $estProprietaire
                ? User::query()
                    ->where('entreprise_id', $user->entreprise_id)
                    ->where('role', RoleEnum::Livreur)
                    ->get(['id', 'name'])
                : [],
            'peutAssigner' => $estProprietaire,
        ]);
    }

    public function assignerResponsable(Request $request, Livraison $livraison, LivraisonService $livraisons): RedirectResponse
    {
        Gate::authorize('assigner', $livraison);

        $data = $request->validate([
            'responsable_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $livraisons->assignerResponsable($livraison, User::findOrFail($data['responsable_user_id']));

        return redirect()->back();
    }
}
