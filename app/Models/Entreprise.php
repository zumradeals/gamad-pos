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
}
