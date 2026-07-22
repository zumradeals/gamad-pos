<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'point_de_vente_id',
    'ouverte_a',
    'statut',
    'especes_attendues',
    'especes_comptees',
    'ecart',
    'depenses_total',
    'validee_par_user_id',
    'validee_a',
    'motif_reouverture',
    'reouverte_par_user_id',
    'reouverte_a',
])]
class Cloture extends Model
{
    use HasFactory;

    public const STATUT_OUVERTE = 'ouverte';

    public const STATUT_VALIDEE = 'validee';

    protected function casts(): array
    {
        return [
            'ouverte_a' => 'datetime',
            'especes_attendues' => 'decimal:2',
            'especes_comptees' => 'decimal:2',
            'ecart' => 'decimal:2',
            'depenses_total' => 'decimal:2',
            'validee_a' => 'datetime',
            'reouverte_a' => 'datetime',
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
    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validee_par_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reouvertePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reouverte_par_user_id');
    }

    /**
     * @return HasMany<Paiement, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * @return HasMany<Versement, $this>
     */
    public function versements(): HasMany
    {
        return $this->hasMany(Versement::class);
    }
}
