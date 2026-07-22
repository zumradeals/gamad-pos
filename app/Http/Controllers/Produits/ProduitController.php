<?php

namespace App\Http\Controllers\Produits;

use App\Http\Controllers\Controller;
use App\Models\MouvementStock;
use App\Models\PointDeVente;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduitController extends Controller
{
    /**
     * Create a produit with its initial stock (a simple reception). Minimal
     * endpoint for testing — not a full catalogue management screen. The
     * produit itself belongs to the entreprise; its opening stock lands at
     * the currently selected point de vente as an emplacement.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prix_vente' => ['required', 'numeric', 'min:0'],
            'unite' => ['required', 'string', 'max:50'],
            'quantite_initiale' => ['required', 'numeric', 'min:0'],
        ]);

        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);

        $pointDeVente = PointDeVente::findOrFail((int) $pointDeVenteId);

        DB::transaction(function () use ($data, $pointDeVente) {
            $produit = Produit::create([
                'entreprise_id' => $pointDeVente->entreprise_id,
                'nom' => $data['nom'],
                'prix_vente' => $data['prix_vente'],
                'unite' => $data['unite'],
            ]);

            $produit->mouvementsStock()->create([
                'emplacement_type' => PointDeVente::class,
                'emplacement_id' => $pointDeVente->id,
                'type' => MouvementStock::TYPE_RECEPTION,
                'quantite' => $data['quantite_initiale'],
            ]);
        });

        return redirect()->route('ventes.create');
    }
}
