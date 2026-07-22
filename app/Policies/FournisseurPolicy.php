<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Fournisseur;
use App\Models\User;

/**
 * À ce stade (avant le chantier Achats), seul un Propriétaire de
 * l'entreprise a accès aux fiches fournisseur — le Magasinier n'en a pas
 * encore besoin, pas même en lecture.
 */
class FournisseurPolicy
{
    public function voir(User $user, Fournisseur $fournisseur): bool
    {
        return $this->estProprietaireDeLEntreprise($user, $fournisseur->entreprise_id);
    }

    public function creer(User $user): bool
    {
        return $user->role === RoleEnum::Proprietaire && $user->entreprise_id !== null;
    }

    public function modifier(User $user, Fournisseur $fournisseur): bool
    {
        return $this->estProprietaireDeLEntreprise($user, $fournisseur->entreprise_id);
    }

    private function estProprietaireDeLEntreprise(User $user, int $entrepriseId): bool
    {
        return $user->role === RoleEnum::Proprietaire
            && $user->entreprise_id !== null
            && $user->entreprise_id === $entrepriseId;
    }
}
