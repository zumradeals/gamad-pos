<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entreprise_id', 'offre_id', 'paiement_origine_id', 'statut', 'date_debut', 'date_echeance'])]
class Abonnement extends Model
{
    use HasFactory;

    public const STATUT_ACTIF = 'actif';

    public const STATUT_EXPIRE = 'expire';

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_echeance' => 'date',
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
     * @return BelongsTo<Offre, $this>
     */
    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    /**
     * @return BelongsTo<PaiementAbonnement, $this>
     */
    public function paiementOrigine(): BelongsTo
    {
        return $this->belongsTo(PaiementAbonnement::class, 'paiement_origine_id');
    }

    /**
     * @return HasMany<PaiementAbonnement, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementAbonnement::class);
    }
}
