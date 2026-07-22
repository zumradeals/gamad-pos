<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Depense;
use App\Models\User;

/**
 * Un Caissier ou un Propriétaire peut enregistrer une dépense. Seul un
 * Propriétaire peut la valider (validation simple, pas de workflow à
 * plusieurs étapes).
 */
class DepensePolicy
{
    public function creer(User $user): bool
    {
        return in_array($user->role, [RoleEnum::Caissier, RoleEnum::Proprietaire], true)
            && $user->entreprise_id !== null;
    }

    public function valider(User $user, Depense $depense): bool
    {
        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $depense->pointDeVente->entreprise_id;
    }
}
