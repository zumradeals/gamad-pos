<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entreprise_id', 'offre_id', 'paiement_origine_id', 'statut', 'date_debut', 'date_echeance'])]
class Abonnement extends Model
{
    use HasFactory;

    public const STATUT_ACTIF = 'actif';

    public const STATUT_EXPIRE = 'expire';

    /**
     * Durée de grâce après l'échéance, durant laquelle l'accès reste
     * complet (proposition documentée, Chantier 9).
     */
    public const JOURS_GRACE = 7;

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_echeance' => 'date',
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
     * @return BelongsTo<Offre, $this>
     */
    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    /**
     * @return BelongsTo<PaiementAbonnement, $this>
     */
    public function paiementOrigine(): BelongsTo
    {
        return $this->belongsTo(PaiementAbonnement::class, 'paiement_origine_id');
    }

    /**
     * @return HasMany<PaiementAbonnement, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementAbonnement::class);
    }

    /**
     * Statut d'accès (à jour / en grâce / suspendu) : jamais stocké, jamais
     * mis à jour par une tâche planifiée — calculé à la volée à partir de
     * date_echeance et de JOURS_GRACE, exactement comme stockDisponible()
     * ou resteDu() avant lui. Un renouvellement (qui avance date_echeance)
     * lève donc la suspension immédiatement, sans aucun champ à
     * réinitialiser.
     */
    public function estEnGrace(): bool
    {
        return now()->greaterThan($this->finPeriodeValidite())
            && now()->lessThanOrEqualTo($this->finPeriodeGrace());
    }

    public function estSuspendu(): bool
    {
        return now()->greaterThan($this->finPeriodeGrace());
    }

    private function finPeriodeValidite(): \Carbon\CarbonInterface
    {
        return $this->date_echeance->endOfDay();
    }

    private function finPeriodeGrace(): \Carbon\CarbonInterface
    {
        return $this->finPeriodeValidite()->addDays(self::JOURS_GRACE);
    }
}
