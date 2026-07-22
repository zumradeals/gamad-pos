<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\PointDeVente;
use App\Models\User;

/**
 * "Mode patron" (Matrice des rôles) : le rapport agrégé n'est visible que
 * par un Propriétaire de l'entreprise à laquelle appartient le point de
 * vente demandé — jamais par un autre rôle, jamais depuis une autre
 * entreprise.
 */
class RapportPolicy
{
    public function voir(User $user, PointDeVente $pointDeVente): bool
    {
        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $pointDeVente->entreprise_id;
    }
}
