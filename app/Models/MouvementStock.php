<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['produit_id', 'emplacement_type', 'emplacement_id', 'type', 'quantite', 'receptionne_at', 'origine_type', 'origine_id'])]
class MouvementStock extends Model
{
    use HasFactory;

    protected $table = 'mouvements_stock';

    public const TYPE_RECEPTION = 'reception';

    public const TYPE_SORTIE_VENTE = 'sortie_vente';

    public const TYPE_CORRECTION = 'correction';

    public const TYPE_TRANSFERT_SORTIE = 'transfert_sortie';

    public const TYPE_TRANSFERT_ENTREE = 'transfert_entree';

    public const TYPE_RESERVATION = 'reservation';

    public const TYPE_LIBERATION_RESERVATION = 'liberation_reservation';

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'receptionne_at' => 'datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function origine(): MorphTo
    {
        return $this->morphTo();
    }
}
