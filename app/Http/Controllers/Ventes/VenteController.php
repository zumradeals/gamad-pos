<?php

namespace App\Http\Controllers\Ventes;

use App\Http\Controllers\Controller;
use App\Models\PointDeVente;
use App\Models\Produit;
use App\Services\VenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VenteController extends Controller
{
    public function create(Request $request): Response
    {
        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);

        $pointDeVente = PointDeVente::findOrFail((int) $pointDeVenteId);

        $produits = Produit::where('entreprise_id', $pointDeVente->entreprise_id)
            ->get()
            ->map(fn (Produit $produit) => [
                'id' => $produit->id,
                'nom' => $produit->nom,
                'prix_vente' => $produit->prix_vente,
                'unite' => $produit->unite,
                'stock_disponible' => $produit->stockDisponible($pointDeVente),
            ])
            ->values();

        return Inertia::render('ventes/create', [
            'produits' => $produits,
            'abonnementSuspendu' => $pointDeVente->entreprise->estSuspendue(),
        ]);
    }

    public function store(Request $request, VenteService $ventes): RedirectResponse
    {
        $data = $request->validate([
            'produit_id' => ['required', 'integer'],
            'quantite' => ['required', 'numeric', 'min:0.001'],
            'montant_paye' => ['required', 'numeric', 'min:0'],
            'client_nom' => ['nullable', 'string', 'max:255'],
            'client_telephone' => ['nullable', 'string', 'max:50'],
            'livraison_lieu' => ['nullable', 'string', 'max:255'],
            'livraison_date_prevue' => ['nullable', 'date'],
        ]);

        $pointDeVenteId = $request->session()->get('point_de_vente_id');

        abort_unless($pointDeVenteId, 403);

        $pointDeVente = PointDeVente::findOrFail((int) $pointDeVenteId);
        $produit = Produit::where('entreprise_id', $pointDeVente->entreprise_id)->findOrFail((int) $data['produit_id']);

        $client = filled($data['client_nom'] ?? null)
            ? ['nom' => $data['client_nom'], 'telephone' => $data['client_telephone'] ?? null]
            : null;

        $livraison = filled($data['livraison_lieu'] ?? null)
            ? ['lieu' => $data['livraison_lieu'], 'date_prevue' => $data['livraison_date_prevue'] ?? null]
            : null;

        $ventes->enregistrerVente(
            vendeur: $request->user(),
            pointDeVente: $pointDeVente,
            produit: $produit,
            quantite: (float) $data['quantite'],
            montantPaye: (float) $data['montant_paye'],
            client: $client,
            livraison: $livraison,
        );

        return redirect()->route('ventes.create');
    }
}
