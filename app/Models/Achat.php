<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['entreprise_id', 'fournisseur_id', 'user_id', 'emplacement_type', 'emplacement_id', 'statut', 'montant_total'])]
class Achat extends Model
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
     * @return BelongsTo<Entreprise, $this>
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * @return BelongsTo<Fournisseur, $this>
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Emplacement polymorphe : Depot ou PointDeVente, même mécanique que
     * MouvementStock (Chantier 6).
     */
    public function emplacement(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<LigneAchat, $this>
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(LigneAchat::class);
    }

    /**
     * @return HasMany<PaiementAchat, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementAchat::class);
    }

    /**
     * @return MorphMany<MouvementStock, $this>
     */
    public function mouvementsStock(): MorphMany
    {
        return $this->morphMany(MouvementStock::class, 'origine');
    }

    /**
     * @return HasOne<DetteFournisseur, $this>
     */
    public function detteFournisseur(): HasOne
    {
        return $this->hasOne(DetteFournisseur::class);
    }
}
