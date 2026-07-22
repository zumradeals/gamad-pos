<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'point_de_vente_id',
    'categorie',
    'montant',
    'justificatif',
    'user_id',
    'validee_par_user_id',
    'statut',
    'cloture_id',
])]
class Depense extends Model
{
    use HasFactory;

    public const STATUT_ENREGISTREE = 'enregistree';

    public const STATUT_VALIDEE = 'validee';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
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
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validee_par_user_id');
    }

    /**
     * @return BelongsTo<Cloture, $this>
     */
    public function cloture(): BelongsTo
    {
        return $this->belongsTo(Cloture::class);
    }
}
