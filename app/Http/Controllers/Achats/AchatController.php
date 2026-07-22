<?php

namespace App\Http\Controllers\Achats;

use App\Http\Controllers\Controller;
use App\Models\Achat;
use App\Models\Depot;
use App\Models\Fournisseur;
use App\Services\AchatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AchatController extends Controller
{
    /**
     * Minimal endpoint (no dedicated screen) — condition préalable au
     * chantier Commandes. La réception se fait vers un dépôt (mécanique
     * d'emplacement du Chantier 6, réutilisée telle quelle).
     */
    public function store(Request $request, AchatService $achats): RedirectResponse
    {
        Gate::authorize('creer', Achat::class);

        $data = $request->validate([
            'fournisseur_id' => ['required', 'integer'],
            'depot_id' => ['required', 'integer'],
            'montant_paye' => ['required', 'numeric', 'min:0'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'integer'],
            'lignes.*.quantite' => ['required', 'numeric', 'min:0.001'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        $fournisseur = Fournisseur::where('entreprise_id', $user->entreprise_id)
            ->findOrFail((int) $data['fournisseur_id']);

        $depot = Depot::where('entreprise_id', $user->entreprise_id)
            ->findOrFail((int) $data['depot_id']);

        $achats->enregistrerAchat(
            createur: $user,
            fournisseur: $fournisseur,
            emplacement: $depot,
            lignes: $data['lignes'],
            montantPaye: (float) $data['montant_paye'],
        );

        return redirect()->back();
    }
}
