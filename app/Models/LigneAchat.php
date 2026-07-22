<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['achat_id', 'produit_id', 'quantite', 'prix_unitaire'])]
class LigneAchat extends Model
{
    use HasFactory;

    protected $table = 'lignes_achat';

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'prix_unitaire' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Achat, $this>
     */
    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    /**
     * @return BelongsTo<Produit, $this>
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
