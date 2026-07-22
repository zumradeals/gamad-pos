<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['fournisseur_id', 'achat_id', 'montant_initial', 'statut'])]
class DetteFournisseur extends Model
{
    use HasFactory;

    protected $table = 'dettes_fournisseur';

    public const STATUT_OUVERTE = 'ouverte';

    public const STATUT_SOLDEE = 'soldee';

    protected function casts(): array
    {
        return [
            'montant_initial' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Fournisseur, $this>
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * @return BelongsTo<Achat, $this>
     */
    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    /**
     * @return HasMany<VersementFournisseur, $this>
     */
    public function versements(): HasMany
    {
        return $this->hasMany(VersementFournisseur::class);
    }

    /**
     * Reste dû = montant initial moins la somme des versements enregistrés.
     * Même règle que Creance::resteDu().
     */
    public function resteDu(): float
    {
        $verse = (float) $this->versements()->sum('montant');

        return round((float) $this->montant_initial - $verse, 2);
    }
}
