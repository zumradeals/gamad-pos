<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'vente_id', 'montant_initial', 'echeance', 'statut'])]
class Creance extends Model
{
    use HasFactory;

    public const STATUT_OUVERTE = 'ouverte';

    public const STATUT_SOLDEE = 'soldee';

    protected function casts(): array
    {
        return [
            'montant_initial' => 'decimal:2',
            'echeance' => 'date',
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
     * @return BelongsTo<Vente, $this>
     */
    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    /**
     * @return HasMany<Versement, $this>
     */
    public function versements(): HasMany
    {
        return $this->hasMany(Versement::class);
    }

    /**
     * Reste dû = montant initial moins la somme des versements enregistrés.
     */
    public function resteDu(): float
    {
        $verse = (float) $this->versements()->sum('montant');

        return round((float) $this->montant_initial - $verse, 2);
    }
}
