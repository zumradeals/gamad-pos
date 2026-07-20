<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['produit_id', 'point_de_vente_id', 'type', 'quantite', 'origine_type', 'origine_id'])]
class MouvementStock extends Model
{
    use HasFactory;

    protected $table = 'mouvements_stock';

    public const TYPE_RECEPTION = 'reception';

    public const TYPE_SORTIE_VENTE = 'sortie_vente';

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
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
     * @return BelongsTo<PointDeVente, $this>
     */
    public function pointDeVente(): BelongsTo
    {
        return $this->belongsTo(PointDeVente::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function origine(): MorphTo
    {
        return $this->morphTo();
    }
}
