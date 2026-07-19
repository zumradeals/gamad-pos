<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\AppareilMemorise;
use App\Models\User;

class AppareilMemorisePolicy
{
    /**
     * An appareil can be revoked by its own owner, or by a Propriétaire of the
     * same entreprise as the appareil's owner. No one else may revoke it.
     */
    public function revoke(User $user, AppareilMemorise $appareil): bool
    {
        if ($user->id === $appareil->user_id) {
            return true;
        }

        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $appareil->user->entreprise_id;
    }
}
