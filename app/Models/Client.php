<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['point_de_vente_id', 'nom', 'telephone'])]
class Client extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<PointDeVente, $this>
     */
    public function pointDeVente(): BelongsTo
    {
        return $this->belongsTo(PointDeVente::class);
    }

    /**
     * @return HasMany<Creance, $this>
     */
    public function creances(): HasMany
    {
        return $this->hasMany(Creance::class);
    }

    /**
     * @return HasMany<Livraison, $this>
     */
    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class);
    }
}
