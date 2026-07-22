<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\LigneLivraison;
use App\Models\Livraison;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LivraisonService
{
    /**
     * Register a delivery pass against a livraison. The quantity can never
     * exceed what's left to deliver — it is rejected outright rather than
     * capped. The livraison is marked livree once nothing remains to
     * deliver, or partielle as long as a positive balance remains after at
     * least one pass.
     */
    public function enregistrerLigneLivraison(Livraison $livraison, float $quantite): LigneLivraison
    {
        $reste = $livraison->resteALivrer();

        if (round($quantite - $reste, 2) > 0) {
            throw ValidationException::withMessages([
                'quantite' => 'La quantité dépasse le reste à livrer.',
            ]);
        }

        return DB::transaction(function () use ($livraison, $quantite, $reste) {
            $ligne = $livraison->lignesLivraison()->create([
                'quantite' => $quantite,
                'date' => now(),
            ]);

            $nouveauReste = round($reste - $quantite, 2);

            $livraison->update([
                'statut' => $nouveauReste <= 0 ? Livraison::STATUT_LIVREE : Livraison::STATUT_PARTIELLE,
            ]);

            return $ligne;
        });
    }

    /**
     * Designate the Livreur responsable for a livraison. Only possible while
     * the livraison hasn't been fully delivered yet, and only for a user who
     * actually holds the Livreur role in the same entreprise as the sale.
     */
    public function assignerResponsable(Livraison $livraison, User $responsable): void
    {
        if (! in_array($livraison->statut, [Livraison::STATUT_PLANIFIEE, Livraison::STATUT_PARTIELLE], true)) {
            throw ValidationException::withMessages([
                'responsable_user_id' => 'Cette livraison ne peut plus recevoir de responsable.',
            ]);
        }

        if ($responsable->role !== RoleEnum::Livreur
            || $responsable->entreprise_id !== $livraison->vente->pointDeVente->entreprise_id) {
            throw ValidationException::withMessages([
                'responsable_user_id' => "Cet utilisateur n'est pas un livreur de cette entreprise.",
            ]);
        }

        $livraison->update(['responsable_user_id' => $responsable->id]);
    }
}
