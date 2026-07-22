<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['point_de_vente_id', 'user_id', 'statut', 'montant_total'])]
class Vente extends Model
{
    use HasFactory;

    public const STATUT_VALIDEE = 'validee';

    protected function casts(): array
    {
        return [
            'montant_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PointDeVente, $this>
     */
    public function pointDeVente(): BelongsTo
    {
        return $this->belongsTo(PointDeVente::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<LigneVente, $this>
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    /**
     * @return HasMany<Paiement, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * @return MorphMany<MouvementStock, $this>
     */
    public function mouvementsStock(): MorphMany
    {
        return $this->morphMany(MouvementStock::class, 'origine');
    }

    /**
     * @return HasOne<Creance, $this>
     */
    public function creance(): HasOne
    {
        return $this->hasOne(Creance::class);
    }

    /**
     * @return HasOne<Livraison, $this>
     */
    public function livraison(): HasOne
    {
        return $this->hasOne(Livraison::class);
    }
}
