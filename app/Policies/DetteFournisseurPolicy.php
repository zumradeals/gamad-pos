<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\DetteFournisseur;
use App\Models\User;

/**
 * Seul un Propriétaire de l'entreprise du fournisseur peut enregistrer un
 * versement sur une dette fournisseur — même périmètre que AchatPolicy.
 */
class DetteFournisseurPolicy
{
    public function verser(User $user, DetteFournisseur $dette): bool
    {
        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $dette->fournisseur->entreprise_id;
    }
}
