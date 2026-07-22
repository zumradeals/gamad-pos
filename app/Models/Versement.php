<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['creance_id', 'cloture_id', 'montant', 'mode'])]
class Versement extends Model
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
     * @return BelongsTo<Creance, $this>
     */
    public function creance(): BelongsTo
    {
        return $this->belongsTo(Creance::class);
    }

    /**
     * @return BelongsTo<Cloture, $this>
     */
    public function cloture(): BelongsTo
    {
        return $this->belongsTo(Cloture::class);
    }
}
