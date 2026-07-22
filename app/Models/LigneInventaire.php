<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['inventaire_id', 'produit_id', 'quantite_theorique', 'quantite_comptee', 'ecart'])]
class LigneInventaire extends Model
{
    use HasFactory;

    protected $table = 'lignes_inventaire';

    protected function casts(): array
    {
        return [
            'quantite_theorique' => 'decimal:3',
            'quantite_comptee' => 'decimal:3',
            'ecart' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Inventaire, $this>
     */
    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    /**
     * @return BelongsTo<Produit, $this>
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * @return HasOne<CorrectionStock, $this>
     */
    public function correctionStock(): HasOne
    {
        return $this->hasOne(CorrectionStock::class);
    }
}
