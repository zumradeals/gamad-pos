<?php

namespace App\Services;

use App\Contracts\ConfirmationPaiementAbonnement;
use App\Models\Abonnement;
use App\Models\Entreprise;
use App\Models\Offre;
use App\Models\PaiementAbonnement;
use Illuminate\Support\Facades\DB;

class AbonnementService
{
    /**
     * Fixed default subscription period. No offre defines its own duration
     * yet — this is an explicit, documented choice, not an implicit one.
     */
    private const DUREE_PERIODE_EN_MOIS = 1;

    /**
     * Activate a subscription from an already-confirmed payment (see
     * PaiementAbonnementProvider — this method never talks to the provider
     * itself, it only ever receives its verdict).
     *
     * Idempotence: a paiementConfirme whose référence externe was already
     * recorded never creates a second abonnement or a second paiement — the
     * abonnement that first payment activated is returned unchanged.
     *
     * Renewal rule (explicit, not left implicit): activating a nouvelle
     * offre for an entreprise that already has un abonnement actif does not
     * create a second one — it prolonge the existing abonnement, whose
     * date_echeance moves one période further from where it already stood.
     * A still-valid subscription is never shortened or replaced by a
     * renewal payment; the new period simply starts where the current one
     * ends. paiement_origine_id is set once, at creation, and never
     * reassigned by a later renewal payment.
     */
    public function activer(Entreprise $entreprise, Offre $offre, ConfirmationPaiementAbonnement $paiementConfirme): Abonnement
    {
        $paiementExistant = PaiementAbonnement::where('reference_externe', $paiementConfirme->referenceExterne)->first();

        if ($paiementExistant !== null) {
            return $paiementExistant->abonnement;
        }

        return DB::transaction(function () use ($entreprise, $offre, $paiementConfirme) {
            $abonnement = Abonnement::where('entreprise_id', $entreprise->id)
                ->where('statut', Abonnement::STATUT_ACTIF)
                ->first();

            if ($abonnement !== null) {
                $abonnement->update([
                    'offre_id' => $offre->id,
                    'date_echeance' => $abonnement->date_echeance->addMonthsNoOverflow(self::DUREE_PERIODE_EN_MOIS),
                ]);
            } else {
                $abonnement = Abonnement::create([
                    'entreprise_id' => $entreprise->id,
                    'offre_id' => $offre->id,
                    'statut' => Abonnement::STATUT_ACTIF,
                    'date_debut' => now()->toDateString(),
                    'date_echeance' => now()->addMonthsNoOverflow(self::DUREE_PERIODE_EN_MOIS)->toDateString(),
                ]);
            }

            $paiement = $abonnement->paiements()->create([
                'montant' => $paiementConfirme->montant,
                'reference_externe' => $paiementConfirme->referenceExterne,
                'statut' => PaiementAbonnement::STATUT_CONFIRME,
                'recu_a' => now(),
            ]);

            if ($abonnement->paiement_origine_id === null) {
                $abonnement->update(['paiement_origine_id' => $paiement->id]);
            }

            return $abonnement->fresh();
        });
    }
}
