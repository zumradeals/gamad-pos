<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['client_id', 'point_de_vente_id', 'devis_id', 'statut', 'montant_total'])]
class Commande extends Model
{
    use HasFactory;

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_PREPAREE = 'preparee';

    public const STATUT_LIVREE = 'livree';

    public const STATUT_ANNULEE = 'annulee';

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
     * Devis d'origine, nullable : une commande peut naître directement, sans
     * ressaisie depuis un devis accepté (Chantier 14).
     *
     * @return BelongsTo<Devis, $this>
     */
    public function devis(): BelongsTo
    {
        return $this->belongsTo(Devis::class);
    }

    /**
     * @return HasMany<LigneCommande, $this>
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }

    /**
     * @return HasMany<Paiement, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * @return HasOne<Creance, $this>
     */
    public function creance(): HasOne
    {
        return $this->hasOne(Creance::class);
    }

    /**
     * Réservations (à la création) et leurs libérations ou sorties réelles
     * (à l'annulation ou la livraison) — même mécanique polymorphique que
     * Vente et Achat (Chantier 6).
     *
     * @return MorphMany<MouvementStock, $this>
     */
    public function mouvementsStock(): MorphMany
    {
        return $this->morphMany(MouvementStock::class, 'origine');
    }
}
