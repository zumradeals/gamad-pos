<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['point_de_vente_id', 'cloture_id', 'type', 'montant', 'motif', 'user_id'])]
class MouvementCaisse extends Model
{
    use HasFactory;

    protected $table = 'mouvements_caisse';

    public const TYPE_FONDS_INITIAL = 'fonds_initial';

    public const TYPE_ENTREE = 'entree';

    public const TYPE_SORTIE = 'sortie';

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
     * @return BelongsTo<Cloture, $this>
     */
    public function cloture(): BelongsTo
    {
        return $this->belongsTo(Cloture::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
