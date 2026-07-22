<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Moteur (deuxième niveau de contrôle, en plus du masquage des boutons côté
 * navigation) : refuse toute écriture métier — vente, versement, ligne de
 * livraison, correction de stock, validation de clôture — si l'entreprise
 * de l'utilisateur a un abonnement suspendu. Ne s'applique jamais à la
 * lecture, à l'export ni à l'authentification. Ne supprime ni ne masque
 * aucune donnée existante.
 */
class EmpecherEcritureSiAbonnementSuspendu
{
    public function handle(Request $request, Closure $next): Response
    {
        $entreprise = $request->user()?->entreprise;

        if ($entreprise?->estSuspendue()) {
            abort(403, "Abonnement suspendu : renouvelez-le pour reprendre les opérations. Vos données restent intactes et consultables via l'export.");
        }

        return $next($request);
    }
}
