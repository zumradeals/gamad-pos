<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vente_id', 'produit_id', 'quantite', 'prix_unitaire'])]
class LigneVente extends Model
{
    use HasFactory;

    protected $table = 'lignes_vente';

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'prix_unitaire' => 'decimal:2',
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
     * @return BelongsTo<Produit, $this>
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
