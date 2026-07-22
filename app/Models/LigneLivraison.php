<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['livraison_id', 'quantite', 'date'])]
class LigneLivraison extends Model
{
    use HasFactory;

    protected $table = 'lignes_livraison';

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'date' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Livraison, $this>
     */
    public function livraison(): BelongsTo
    {
        return $this->belongsTo(Livraison::class);
    }
}
