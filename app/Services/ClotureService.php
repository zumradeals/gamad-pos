<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Cloture;
use App\Models\Depense;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\User;
use App\Models\Versement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClotureService
{
    /**
     * Open a new clôture for a point de vente. Only a caissier or a
     * propriétaire may do so, and only one clôture may be ouverte at a time
     * per point de vente — otherwise which one a payment belongs to would be
     * ambiguous.
     */
    public function ouvrir(PointDeVente $pointDeVente, User $ouvrePar): Cloture
    {
        if (! in_array($ouvrePar->role, [RoleEnum::Caissier, RoleEnum::Proprietaire], true)) {
            throw ValidationException::withMessages([
                'role' => 'Seul un caissier ou un propriétaire peut ouvrir une clôture.',
            ]);
        }

        if (Cloture::where('point_de_vente_id', $pointDeVente->id)->where('statut', Cloture::STATUT_OUVERTE)->exists()) {
            throw ValidationException::withMessages([
                'statut' => 'Une clôture est déjà ouverte pour ce point de vente.',
            ]);
        }

        return Cloture::create([
            'point_de_vente_id' => $pointDeVente->id,
            'ouverte_a' => now(),
            'statut' => Cloture::STATUT_OUVERTE,
        ]);
    }

    /**
     * Espèces attendues = somme des paiements espèces + versements espèces du
     * point de vente jamais encore rattachés à une clôture. Toujours calculé
     * à la volée, jamais lu depuis un total stocké.
     */
    public function especesAttendues(PointDeVente $pointDeVente): float
    {
        $paiements = (float) $this->paiementsNonRattaches($pointDeVente)->sum('montant');
        $versements = (float) $this->versementsNonRattaches($pointDeVente)->sum('montant');

        return round($paiements + $versements, 2);
    }

    /**
     * Dépenses total = somme des dépenses validee du point de vente jamais
     * encore rattachées à une clôture. Toujours calculé à la volée, jamais
     * lu depuis un total stocké — même règle que especesAttendues(). Une
     * dépense encore enregistree (non validée) n'entre pas dans ce calcul.
     */
    public function depensesTotal(PointDeVente $pointDeVente): float
    {
        return round((float) $this->depensesNonRattachees($pointDeVente)->sum('montant'), 2);
    }

    /**
     * Validate an ouverte clôture: recomputes espèces attendues at this exact
     * moment, compares it to the espèces comptées, stores the résultant
     * écart, rattache every covered paiement/versement/dépense so it can
     * never be counted again, and freezes the clôture as validée (invariant
     * H1: it can never be validated — i.e. written to — a second time).
     */
    public function valider(Cloture $cloture, float $especesComptees, User $validePar): Cloture
    {
        if ($cloture->statut !== Cloture::STATUT_OUVERTE) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une clôture ouverte peut être validée.',
            ]);
        }

        if (! in_array($validePar->role, [RoleEnum::Caissier, RoleEnum::Proprietaire], true)) {
            throw ValidationException::withMessages([
                'role' => 'Seul un caissier ou un propriétaire peut valider une clôture.',
            ]);
        }

        return DB::transaction(function () use ($cloture, $especesComptees, $validePar) {
            $pointDeVente = $cloture->pointDeVente;

            $paiements = $this->paiementsNonRattaches($pointDeVente)->get();
            $versements = $this->versementsNonRattaches($pointDeVente)->get();
            $depenses = $this->depensesNonRattachees($pointDeVente)->get();

            $especesAttendues = round((float) $paiements->sum('montant') + (float) $versements->sum('montant'), 2);
            $ecart = round($especesComptees - $especesAttendues, 2);
            $depensesTotal = round((float) $depenses->sum('montant'), 2);

            Paiement::whereIn('id', $paiements->pluck('id'))->update(['cloture_id' => $cloture->id]);
            Versement::whereIn('id', $versements->pluck('id'))->update(['cloture_id' => $cloture->id]);
            Depense::whereIn('id', $depenses->pluck('id'))->update(['cloture_id' => $cloture->id]);

            $cloture->update([
                'especes_attendues' => $especesAttendues,
                'especes_comptees' => $especesComptees,
                'ecart' => $ecart,
                'depenses_total' => $depensesTotal,
                'validee_par_user_id' => $validePar->id,
                'validee_a' => now(),
                'statut' => Cloture::STATUT_VALIDEE,
            ]);

            return $cloture;
        });
    }

    /**
     * Reopen a validée clôture (invariant H2). This never touches the écart
     * already constaté on this row — it stays in place, in the history, as
     * is. Reopening only records who/why/when, and opens a brand new clôture
     * for the same point de vente; a fresh écart appears only once that new
     * clôture is itself validated.
     */
    public function reouvrir(Cloture $cloture, ?string $motif, ?User $proprietaire): Cloture
    {
        if ($cloture->statut !== Cloture::STATUT_VALIDEE) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une clôture validée peut être réouverte.',
            ]);
        }

        if (blank($motif)) {
            throw ValidationException::withMessages([
                'motif_reouverture' => 'Un motif est requis pour réouvrir une clôture.',
            ]);
        }

        if ($proprietaire === null
            || $proprietaire->role !== RoleEnum::Proprietaire
            || $proprietaire->entreprise_id !== $cloture->pointDeVente->entreprise_id) {
            throw ValidationException::withMessages([
                'reouverte_par_user_id' => 'Seul un propriétaire de cette entreprise peut réouvrir une clôture.',
            ]);
        }

        return DB::transaction(function () use ($cloture, $motif, $proprietaire) {
            $cloture->update([
                'motif_reouverture' => $motif,
                'reouverte_par_user_id' => $proprietaire->id,
                'reouverte_a' => now(),
            ]);

            return Cloture::create([
                'point_de_vente_id' => $cloture->point_de_vente_id,
                'ouverte_a' => now(),
                'statut' => Cloture::STATUT_OUVERTE,
            ]);
        });
    }

    private function paiementsNonRattaches(PointDeVente $pointDeVente)
    {
        return Paiement::whereNull('cloture_id')
            ->where('mode', Paiement::MODE_ESPECES)
            ->whereHas('vente', fn ($query) => $query->where('point_de_vente_id', $pointDeVente->id));
    }

    private function versementsNonRattaches(PointDeVente $pointDeVente)
    {
        return Versement::whereNull('cloture_id')
            ->where('mode', Versement::MODE_ESPECES)
            ->whereHas('creance.vente', fn ($query) => $query->where('point_de_vente_id', $pointDeVente->id));
    }

    private function depensesNonRattachees(PointDeVente $pointDeVente)
    {
        return Depense::whereNull('cloture_id')
            ->where('statut', Depense::STATUT_VALIDEE)
            ->where('point_de_vente_id', $pointDeVente->id);
    }
}
