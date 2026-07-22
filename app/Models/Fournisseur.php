<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entreprise_id', 'nom', 'telephone', 'conditions_commerciales', 'delais_habituels'])]
class Fournisseur extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Entreprise, $this>
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * @return HasMany<Achat, $this>
     */
    public function achats(): HasMany
    {
        return $this->hasMany(Achat::class);
    }

    /**
     * @return HasMany<DetteFournisseur, $this>
     */
    public function dettesFournisseur(): HasMany
    {
        return $this->hasMany(DetteFournisseur::class);
    }
}
