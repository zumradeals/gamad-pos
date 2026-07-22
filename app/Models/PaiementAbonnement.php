<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['abonnement_id', 'montant', 'reference_externe', 'statut', 'recu_a'])]
class PaiementAbonnement extends Model
{
    use HasFactory;

    protected $table = 'paiements_abonnement';

    public const STATUT_CONFIRME = 'confirme';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'recu_a' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Abonnement, $this>
     */
    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(Abonnement::class);
    }
}
