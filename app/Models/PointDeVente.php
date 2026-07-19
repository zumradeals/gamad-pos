<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['entreprise_id', 'nom', 'adresse'])]
class PointDeVente extends Model
{
    use HasFactory;

    protected $table = 'points_de_vente';

    /**
     * @return BelongsTo<Entreprise, $this>
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'point_de_vente_user');
    }
}
