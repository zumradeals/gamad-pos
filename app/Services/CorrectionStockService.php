<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\CorrectionStock;
use App\Models\LigneInventaire;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionStockService
{
    /**
     * Record a proposed correction without touching stock — used
     * automatically by an inventaire line with a non-zero écart. Nothing
     * moves until the same authorization rule (invariant A2) as a manual
     * correction is satisfied via autoriser().
     */
    public function proposer(Produit $produit, Model $emplacement, float $ecart, ?LigneInventaire $ligneInventaire = null): CorrectionStock
    {
        return CorrectionStock::create([
            'produit_id' => $produit->id,
            'emplacement_type' => $emplacement::class,
            'emplacement_id' => $emplacement->id,
            'ligne_inventaire_id' => $ligneInventaire?->id,
            'ecart' => $ecart,
            'statut' => CorrectionStock::STATUT_PROPOSEE,
        ]);
    }

    /**
     * Authorize a previously proposed correction (typically inventory
     * generated). A rejection leaves the correction untouched — still
     * proposée, no stock movement created.
     */
    public function autoriser(CorrectionStock $correction, ?string $motif, ?User $autorisePar): CorrectionStock
    {
        if ($correction->statut !== CorrectionStock::STATUT_PROPOSEE) {
            throw ValidationException::withMessages([
                'statut' => 'Cette correction a déjà été traitée.',
            ]);
        }

        $this->verifierAutorisation($motif, $autorisePar, $correction->emplacement);

        return DB::transaction(function () use ($correction, $motif, $autorisePar) {
            $mouvement = $correction->produit->mouvementsStock()->create([
                'emplacement_type' => $correction->emplacement_type,
                'emplacement_id' => $correction->emplacement_id,
                'type' => MouvementStock::TYPE_CORRECTION,
                'quantite' => $correction->ecart,
            ]);

            $correction->update([
                'motif' => $motif,
                'autorise_par_user_id' => $autorisePar->id,
                'mouvement_stock_id' => $mouvement->id,
                'statut' => CorrectionStock::STATUT_APPLIQUEE,
            ]);

            return $correction;
        });
    }

    /**
     * Create and authorize a manual correction in one step (invariant A2):
     * without a motif or without a valid autorisePar, nothing is persisted
     * at all — no proposed correction is left dangling.
     */
    public function creerManuelle(Produit $produit, Model $emplacement, float $ecart, ?string $motif, ?User $autorisePar): CorrectionStock
    {
        $this->verifierAutorisation($motif, $autorisePar, $emplacement);

        return DB::transaction(function () use ($produit, $emplacement, $ecart, $motif, $autorisePar) {
            $mouvement = $produit->mouvementsStock()->create([
                'emplacement_type' => $emplacement::class,
                'emplacement_id' => $emplacement->id,
                'type' => MouvementStock::TYPE_CORRECTION,
                'quantite' => $ecart,
            ]);

            return CorrectionStock::create([
                'produit_id' => $produit->id,
                'emplacement_type' => $emplacement::class,
                'emplacement_id' => $emplacement->id,
                'mouvement_stock_id' => $mouvement->id,
                'ecart' => $ecart,
                'motif' => $motif,
                'autorise_par_user_id' => $autorisePar->id,
                'statut' => CorrectionStock::STATUT_APPLIQUEE,
            ]);
        });
    }

    /**
     * Invariant A2: a correction always requires an explicit motif and an
     * autorisePar who is a Propriétaire of the entreprise owning the
     * emplacement.
     */
    private function verifierAutorisation(?string $motif, ?User $autorisePar, Model $emplacement): void
    {
        if (blank($motif)) {
            throw ValidationException::withMessages([
                'motif' => 'Un motif est requis pour une correction de stock.',
            ]);
        }

        if ($autorisePar === null
            || $autorisePar->role !== RoleEnum::Proprietaire
            || $autorisePar->entreprise_id !== $emplacement->entreprise_id) {
            throw ValidationException::withMessages([
                'autorise_par_user_id' => 'Seul un propriétaire de cette entreprise peut autoriser cette correction.',
            ]);
        }
    }
}
