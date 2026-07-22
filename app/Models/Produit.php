<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entreprise_id', 'nom', 'prix_vente', 'unite'])]
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
     * @return BelongsTo<Entreprise, $this>
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * @return HasMany<MouvementStock, $this>
     */
    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    /**
     * Stock disponible à un emplacement donné (point de vente ou dépôt) :
     * réceptions, entrées de transfert réceptionnées et corrections moins
     * sorties de vente et sorties de transfert, à cet emplacement précis.
     * Un stock en transit (transfert non encore réceptionné) ne compte nulle
     * part tant qu'il n'a pas été confirmé à destination.
     */
    public function stockDisponible(Model $emplacement): float
    {
        $total = $this->mouvementsStock()
            ->where('emplacement_type', $emplacement::class)
            ->where('emplacement_id', $emplacement->id)
            ->where(function ($query) {
                $query->whereNot('type', MouvementStock::TYPE_TRANSFERT_ENTREE)
                    ->orWhereNotNull('receptionne_at');
            })
            ->selectRaw(
                'SUM(CASE
                    WHEN type IN (?, ?) THEN quantite
                    WHEN type = ? THEN quantite
                    WHEN type IN (?, ?) THEN -quantite
                    ELSE 0
                END) AS total',
                [
                    MouvementStock::TYPE_RECEPTION,
                    MouvementStock::TYPE_TRANSFERT_ENTREE,
                    MouvementStock::TYPE_CORRECTION,
                    MouvementStock::TYPE_SORTIE_VENTE,
                    MouvementStock::TYPE_TRANSFERT_SORTIE,
                ]
            )
            ->value('total');

        return round((float) ($total ?? 0), 3);
    }
}
