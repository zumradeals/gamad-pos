<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['point_de_vente_id', 'nom', 'prix_vente', 'unite'])]
class Produit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'prix_vente' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PointDeVente, $this>
     */
    public function pointDeVente(): BelongsTo
    {
        return $this->belongsTo(PointDeVente::class);
    }

    /**
     * @return HasMany<MouvementStock, $this>
     */
    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    /**
     * Stock disponible = somme des réceptions moins somme des sorties de vente.
     */
    public function stockDisponible(): float
    {
        $total = $this->mouvementsStock()
            ->selectRaw('SUM(CASE WHEN type = ? THEN quantite ELSE -quantite END) AS total', [MouvementStock::TYPE_RECEPTION])
            ->value('total');

        return (float) ($total ?? 0);
    }
}
