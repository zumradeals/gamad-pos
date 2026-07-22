<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['client_id', 'point_de_vente_id', 'statut', 'montant_total'])]
class Devis extends Model
{
    use HasFactory;

    public const STATUT_PROPOSE = 'propose';

    public const STATUT_ACCEPTE = 'accepte';

    public const STATUT_REFUSE = 'refuse';

    public const STATUT_EXPIRE = 'expire';

    protected function casts(): array
    {
        return [
            'montant_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<PointDeVente, $this>
     */
    public function pointDeVente(): BelongsTo
    {
        return $this->belongsTo(PointDeVente::class);
    }

    /**
     * @return HasMany<LigneDevis, $this>
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(LigneDevis::class);
    }

    /**
     * @return HasOne<Commande, $this>
     */
    public function commande(): HasOne
    {
        return $this->hasOne(Commande::class);
    }
}
