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
}
