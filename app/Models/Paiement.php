<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vente_id', 'cloture_id', 'montant', 'mode'])]
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
     * @return BelongsTo<Cloture, $this>
     */
    public function cloture(): BelongsTo
    {
        return $this->belongsTo(Cloture::class);
    }
}
