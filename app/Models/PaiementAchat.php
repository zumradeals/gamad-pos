<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['achat_id', 'montant', 'mode'])]
class PaiementAchat extends Model
{
    use HasFactory;

    protected $table = 'paiements_achat';

    public const MODE_ESPECES = 'especes';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Achat, $this>
     */
    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }
}
