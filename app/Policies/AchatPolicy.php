<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Achat;
use App\Models\User;

/**
 * Comme pour les fournisseurs (Chantier 10), seul un Propriétaire de
 * l'entreprise a accès aux achats à ce stade — le Magasinier reste limité à
 * la réception déjà en place (Chantier 6).
 */
class AchatPolicy
{
    public function voir(User $user, Achat $achat): bool
    {
        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $achat->entreprise_id;
    }

    public function creer(User $user): bool
    {
        return $user->role === RoleEnum::Proprietaire && $user->entreprise_id !== null;
    }
}
