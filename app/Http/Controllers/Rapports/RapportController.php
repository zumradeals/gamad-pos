<?php

namespace App\Http\Controllers\Rapports;

use App\Http\Controllers\Controller;
use App\Models\PointDeVente;
use App\Policies\RapportPolicy;
use App\Services\RapportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RapportController extends Controller
{
    /**
     * JSON minimal, sans écran dédié (comme ExportController) : un tableau
     * simple suffit à ce stade, aucun graphique. Jamais gated par
     * abonnement.actif — un rapport est une lecture, exactement comme
     * l'export ; une suspension d'abonnement restreint l'écriture, jamais
     * la lecture de données déjà existantes (invariant F2).
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'point_de_vente_id' => ['required', 'integer'],
            'debut' => ['required', 'date'],
            'fin' => ['required', 'date', 'after_or_equal:debut'],
        ]);

        $pointDeVente = PointDeVente::findOrFail((int) $data['point_de_vente_id']);

        abort_unless(app(RapportPolicy::class)->voir($request->user(), $pointDeVente), 403);

        $debut = Carbon::parse($data['debut'])->startOfDay();
        $fin = Carbon::parse($data['fin'])->endOfDay();

        return response()->json(app(RapportService::class)->genererRapport($pointDeVente, $debut, $fin));
    }
}
