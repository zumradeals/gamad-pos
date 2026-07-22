<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vente_id', 'client_id', 'lieu', 'date_prevue', 'responsable_user_id', 'statut', 'preuve'])]
class Livraison extends Model
{
    use HasFactory;

    public const STATUT_PLANIFIEE = 'planifiee';

    public const STATUT_PARTIELLE = 'partielle';

    public const STATUT_LIVREE = 'livree';

    protected function casts(): array
    {
        return [
            'date_prevue' => 'date',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    /**
     * @return HasMany<LigneLivraison, $this>
     */
    public function lignesLivraison(): HasMany
    {
        return $this->hasMany(LigneLivraison::class);
    }

    public function quantiteVendue(): float
    {
        return (float) $this->vente->lignes()->sum('quantite');
    }

    public function quantiteLivree(): float
    {
        return (float) $this->lignesLivraison()->sum('quantite');
    }

    /**
     * Reste à livrer = quantité vendue moins la somme des quantités déjà livrées.
     */
    public function resteALivrer(): float
    {
        return round($this->quantiteVendue() - $this->quantiteLivree(), 2);
    }
}
