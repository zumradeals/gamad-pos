<?php

namespace App\Http\Controllers\Abonnements;

use App\Contracts\PaiementAbonnementProvider;
use App\Http\Controllers\Controller;
use App\Models\Offre;
use App\Services\AbonnementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AbonnementController extends Controller
{
    /**
     * Minimal endpoint sufficient to exercise activation end-to-end — not a
     * full offer-selection screen. The provider is resolved from the
     * container (bound to the fake implementation for now); this controller
     * never knows or cares which one it is.
     */
    public function activer(Request $request, PaiementAbonnementProvider $provider, AbonnementService $abonnements): RedirectResponse
    {
        $data = $request->validate([
            'offre_id' => ['required', 'integer', 'exists:offres,id'],
            'montant' => ['required', 'numeric', 'min:0'],
            'reference_externe' => ['required', 'string', 'max:255'],
        ]);

        $entreprise = $request->user()->entreprise;

        abort_unless($entreprise, 403);

        $offre = Offre::findOrFail($data['offre_id']);

        $confirmation = $provider->confirmer([
            'montant' => $data['montant'],
            'reference_externe' => $data['reference_externe'],
        ]);

        $abonnements->activer($entreprise, $offre, $confirmation);

        return redirect()->back();
    }
}
