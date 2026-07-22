<?php

namespace App\Http\Controllers\Livraisons;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Livraison;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LivraisonController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $livraisons = $user->role === RoleEnum::Proprietaire
            ? Livraison::query()
                ->whereHas('vente.pointDeVente', fn ($query) => $query->where('entreprise_id', $user->entreprise_id))
                ->with('client:id,nom')
                ->get()
            : $user->livraisons()->with('client:id,nom')->get();

        return Inertia::render('livraisons/index', [
            'livraisons' => $livraisons->map(fn (Livraison $livraison) => [
                'id' => $livraison->id,
                'lieu' => $livraison->lieu,
                'statut' => $livraison->statut,
                'client' => $livraison->client->nom,
                'reste_a_livrer' => $livraison->resteALivrer(),
            ]),
        ]);
    }
}
