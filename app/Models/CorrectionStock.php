<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'produit_id',
    'emplacement_type',
    'emplacement_id',
    'ligne_inventaire_id',
    'mouvement_stock_id',
    'ecart',
    'motif',
    'autorise_par_user_id',
    'statut',
])]
class CorrectionStock extends Model
{
    use HasFactory;

    protected $table = 'corrections_stock';

    public const STATUT_PROPOSEE = 'proposee';

    public const STATUT_APPLIQUEE = 'appliquee';

    protected function casts(): array
    {
        return [
            'ecart' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Produit, $this>
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function emplacement(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<LigneInventaire, $this>
     */
    public function ligneInventaire(): BelongsTo
    {
        return $this->belongsTo(LigneInventaire::class);
    }

    /**
     * @return BelongsTo<MouvementStock, $this>
     */
    public function mouvementStock(): BelongsTo
    {
        return $this->belongsTo(MouvementStock::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autorisePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorise_par_user_id');
    }
}
