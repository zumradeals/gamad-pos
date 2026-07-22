<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Cloture;
use App\Models\Depense;
use App\Models\MouvementCaisse;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\User;
use App\Models\Versement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClotureService
{
    /**
     * Open a new clôture for a point de vente, with an optional fonds
     * initial (peut être zéro). Only a caissier or a propriétaire may do so,
     * and only one clôture may be ouverte at a time per point de vente —
     * otherwise which one a payment belongs to would be ambiguous. The
     * fonds initial is recorded as a mouvement_caisse rattaché à la clôture
     * dès sa création — contrairement aux paiements/versements/dépenses, il
     * n'y a ici rien à "couvrir" a posteriori : le fonds initial n'existe
     * que parce que cette clôture s'ouvre, il lui appartient dès l'origine.
     */
    public function ouvrir(PointDeVente $pointDeVente, User $ouvrePar, float $fondsInitial = 0.0): Cloture
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

        return DB::transaction(function () use ($pointDeVente, $ouvrePar, $fondsInitial) {
            $cloture = Cloture::create([
                'point_de_vente_id' => $pointDeVente->id,
                'ouverte_a' => now(),
                'statut' => Cloture::STATUT_OUVERTE,
            ]);

            $cloture->mouvementsCaisse()->create([
                'point_de_vente_id' => $pointDeVente->id,
                'type' => MouvementCaisse::TYPE_FONDS_INITIAL,
                'montant' => $fondsInitial,
                'user_id' => $ouvrePar->id,
            ]);

            return $cloture;
        });
    }

    /**
     * Record a cash movement hors vente (dépôt, retrait, apport) against the
     * clôture actuellement ouverte pour ce point de vente. Refusé s'il n'y a
     * pas de clôture ouverte — pas de mouvement de caisse orphelin. Comme le
     * fonds initial, ce mouvement est rattaché à sa clôture dès sa création,
     * jamais seulement à la validation : il naît déjà à l'intérieur d'une
     * clôture ouverte, contrairement aux paiements/versements/dépenses qui
     * peuvent préexister à toute clôture et n'y sont rattachés qu'au moment
     * où l'une d'elles les couvre.
     */
    public function enregistrerMouvementCaisse(
        PointDeVente $pointDeVente,
        string $type,
        float $montant,
        ?string $motif,
        User $createur,
    ): MouvementCaisse {
        if (! in_array($type, [MouvementCaisse::TYPE_ENTREE, MouvementCaisse::TYPE_SORTIE], true)) {
            throw ValidationException::withMessages([
                'type' => 'Type de mouvement de caisse invalide.',
            ]);
        }

        if (! in_array($createur->role, [RoleEnum::Caissier, RoleEnum::Proprietaire], true)) {
            throw ValidationException::withMessages([
                'role' => 'Seul un caissier ou un propriétaire peut enregistrer un mouvement de caisse.',
            ]);
        }

        $cloture = Cloture::where('point_de_vente_id', $pointDeVente->id)
            ->where('statut', Cloture::STATUT_OUVERTE)
            ->first();

        if ($cloture === null) {
            throw ValidationException::withMessages([
                'cloture' => 'Aucune clôture ouverte pour ce point de vente.',
            ]);
        }

        return $cloture->mouvementsCaisse()->create([
            'point_de_vente_id' => $pointDeVente->id,
            'type' => $type,
            'montant' => $montant,
            'motif' => $motif,
            'user_id' => $createur->id,
        ]);
    }

    /**
     * Espèces attendues = fonds initial + entrées − sorties (mouvements de
     * caisse de la clôture actuellement ouverte) + paiements espèces +
     * versements espèces client, jamais encore rattachés à une clôture.
     * Toujours calculé à la volée, jamais lu depuis un total stocké.
     *
     * Chantier 16 (correctif) : couvre désormais aussi bien les paiements
     * (acomptes) et créances nés d'une commande (Chantier 14) que ceux nés
     * d'une vente — un acompte en espèces est un encaissement physique réel
     * au point de vente, quelle que soit l'origine qui l'a produit.
     * L'écart découvert au Chantier 15 (RapportService::recettes() les
     * comptait déjà, especesAttendues() non) est ainsi comblé.
     *
     * Choix explicite sur la rétroactivité : AUCUN correctif rétroactif
     * n'est appliqué aux clôtures déjà validées avant ce chantier — leur
     * especes_attendues et leur écart restent exactement ce qu'ils étaient
     * au moment de la validation (invariants H1/H2 : une clôture validée
     * n'est jamais réécrite, un écart déjà constaté n'est jamais corrigé
     * silencieusement). Concrètement, un paiement ou versement lié à une
     * commande n'a jamais eu son cloture_id renseigné avant ce correctif
     * (l'ancienne requête ne le sélectionnait pas) : il reste donc encore
     * aujourd'hui avec cloture_id = NULL, même s'il date d'une période déjà
     * couverte par une clôture validée entre-temps. Ce correctif ne
     * l'attache pas rétroactivement à cette clôture-là (déjà figée) ; il
     * sera simplement récupéré par la prochaine clôture validée pour ce
     * point de vente — exactement le même mécanisme de rattrapage
     * "non-rattaché" qui existe déjà pour tout paiement de vente en retard.
     * Aucune migration de données, aucun backfill : rien à écrire, la
     * requête elle-même suffit à rattraper le passé au bon moment.
     *
     * Les versements fournisseur (Chantier 11) sont volontairement exclus :
     * une dette fournisseur appartient à un Fournisseur, lui-même rattaché à
     * l'entreprise entière, pas à un point de vente précis (une dette peut
     * naître d'un achat réceptionné dans un dépôt distinct de tout point de
     * vente) — il n'existe donc pas de lien fiable entre un versement
     * fournisseur et la caisse physique d'un point de vente donné, et
     * versements_fournisseur ne porte d'ailleurs aucune colonne cloture_id.
     * Un paiement à un fournisseur qui sort réellement de la caisse d'un
     * point de vente doit être enregistré explicitement comme un mouvement
     * de caisse de type sortie (avec motif), pas déduit implicitement d'une
     * dette fournisseur.
     */
    public function especesAttendues(PointDeVente $pointDeVente): float
    {
        $paiements = (float) $this->paiementsNonRattaches($pointDeVente)->sum('montant');
        $versements = (float) $this->versementsNonRattaches($pointDeVente)->sum('montant');
        $mouvementsCaisse = $this->mouvementsCaisseNetPourPointDeVenteOuvert($pointDeVente);

        return round($paiements + $versements + $mouvementsCaisse, 2);
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
     * Même dépenses que depensesTotal() — validée, de ce point de vente —
     * mais bornées par une plage de dates plutôt que par
     * "non rattachée à une clôture". Utilisé par RapportService (Chantier
     * 15) : un rapport en lecture seule sur une période choisie n'a besoin
     * d'aucune notion de clôture, seulement d'un intervalle de temps.
     */
    public function depensesSurPeriode(PointDeVente $pointDeVente, Carbon $debut, Carbon $fin): float
    {
        return round(
            (float) $this->depensesDuPointDeVente($pointDeVente)
                ->whereBetween('created_at', [$debut, $fin])
                ->sum('montant'),
            2
        );
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
            $mouvementsCaisse = $this->mouvementsCaisseNet($cloture);

            $especesAttendues = round((float) $paiements->sum('montant') + (float) $versements->sum('montant') + $mouvementsCaisse, 2);
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

    /**
     * Un paiement en espèces de ce point de vente, qu'il vienne d'une vente
     * ou d'un acompte de commande (Chantier 14) — jamais les deux relations
     * renseignées à la fois sur un même paiement (Chantier 14), donc les
     * deux branches du orWhereHas ne se chevauchent jamais.
     */
    private function paiementsNonRattaches(PointDeVente $pointDeVente)
    {
        return Paiement::whereNull('cloture_id')
            ->where('mode', Paiement::MODE_ESPECES)
            ->where(function ($query) use ($pointDeVente) {
                $query->whereHas('vente', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id))
                    ->orWhereHas('commande', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id));
            });
    }

    /**
     * Même règle que paiementsNonRattaches() : la créance réglée par ce
     * versement peut venir d'une vente ou d'une commande.
     */
    private function versementsNonRattaches(PointDeVente $pointDeVente)
    {
        return Versement::whereNull('cloture_id')
            ->where('mode', Versement::MODE_ESPECES)
            ->whereHas('creance', function ($query) use ($pointDeVente) {
                $query->whereHas('vente', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id))
                    ->orWhereHas('commande', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id));
            });
    }

    private function depensesNonRattachees(PointDeVente $pointDeVente)
    {
        return $this->depensesDuPointDeVente($pointDeVente)->whereNull('cloture_id');
    }

    /**
     * Base commune à depensesNonRattachees() et depensesSurPeriode() :
     * dépenses validées de ce point de vente, sans préjuger de leur
     * rattachement à une clôture ni d'une quelconque période.
     */
    private function depensesDuPointDeVente(PointDeVente $pointDeVente)
    {
        return Depense::where('point_de_vente_id', $pointDeVente->id)
            ->where('statut', Depense::STATUT_VALIDEE);
    }

    /**
     * Net des mouvements de caisse (fonds initial + entrées − sorties) déjà
     * rattachés à cette clôture précise — contrairement aux méthodes
     * *NonRattaches ci-dessus, il n'y a pas de filtre whereNull('cloture_id')
     * à faire ici : ces mouvements sont toujours déjà rattachés.
     */
    private function mouvementsCaisseNet(Cloture $cloture): float
    {
        $total = MouvementCaisse::where('cloture_id', $cloture->id)
            ->get()
            ->sum(fn (MouvementCaisse $mouvement) => $mouvement->type === MouvementCaisse::TYPE_SORTIE
                ? -(float) $mouvement->montant
                : (float) $mouvement->montant);

        return round($total, 2);
    }

    /**
     * Même calcul que mouvementsCaisseNet(), mais à partir du point de vente
     * plutôt que d'une clôture déjà en main : résout la clôture ouverte
     * courante (s'il y en a une) avant de sommer ses mouvements de caisse.
     */
    private function mouvementsCaisseNetPourPointDeVenteOuvert(PointDeVente $pointDeVente): float
    {
        $clotureOuverte = Cloture::where('point_de_vente_id', $pointDeVente->id)
            ->where('statut', Cloture::STATUT_OUVERTE)
            ->first();

        if ($clotureOuverte === null) {
            return 0.0;
        }

        return $this->mouvementsCaisseNet($clotureOuverte);
    }
}
