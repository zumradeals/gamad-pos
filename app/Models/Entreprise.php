<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'secteur_activite', 'devise', 'pays'])]
class Entreprise extends Model
{
    use HasFactory;

    /**
     * @return HasMany<PointDeVente, $this>
     */
    public function pointsDeVente(): HasMany
    {
        return $this->hasMany(PointDeVente::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Depot, $this>
     */
    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    /**
     * @return HasMany<Produit, $this>
     */
    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class);
    }

    /**
     * @return HasMany<Abonnement, $this>
     */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    public function abonnementActif(): ?Abonnement
    {
        return $this->abonnements()->where('statut', Abonnement::STATUT_ACTIF)->first();
    }

    /**
     * An entreprise that never activated any abonnement is not suspended by
     * this check — Chantier 9 restricts writes for an abonnement that
     * lapsed past its échéance + grâce, not for the separate (and here,
     * unhandled) question of an entreprise that never subscribed at all.
     */
    public function estSuspendue(): bool
    {
        return $this->abonnementActif()?->estSuspendu() ?? false;
    }
}
