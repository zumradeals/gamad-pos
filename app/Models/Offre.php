<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'nom', 'description', 'limite_points_de_vente', 'limite_utilisateurs'])]
class Offre extends Model
{
    use HasFactory;

    public const DECOUVERTE = 'decouverte';

    public const SOLO = 'solo';

    public const COMMERCE = 'commerce';

    public const RESEAU = 'reseau';

    public const ENTREPRISE = 'entreprise';

    /**
     * @return HasMany<Abonnement, $this>
     */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }
}
