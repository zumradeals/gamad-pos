<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Livraison;
use App\Models\User;

class LivraisonPolicy
{
    /**
     * A livraison can be marked as delivered by the Livreur assigned to it
     * as responsable, or by a Propriétaire of the same entreprise as the
     * sale it originates from. No one else may act on it.
     */
    public function livrer(User $user, Livraison $livraison): bool
    {
        if ($user->role === RoleEnum::Livreur) {
            return $user->id === $livraison->responsable_user_id;
        }

        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $livraison->vente->pointDeVente->entreprise_id;
    }
}
