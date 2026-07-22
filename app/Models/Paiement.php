<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vente_id', 'commande_id', 'cloture_id', 'montant', 'mode'])]
class Paiement extends Model
{
    use HasFactory;

    public const MODE_ESPECES = 'especes';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Vente, $this>
     */
    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    /**
     * Un acompte de commande (Chantier 14) plutôt qu'un paiement de vente —
     * jamais les deux à la fois pour un même paiement.
     *
     * @return BelongsTo<Commande, $this>
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * @return BelongsTo<Cloture, $this>
     */
    public function cloture(): BelongsTo
    {
        return $this->belongsTo(Cloture::class);
    }
}
