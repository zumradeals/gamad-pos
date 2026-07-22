<?php

namespace App\Services;

use App\Models\Achat;
use App\Models\Cloture;
use App\Models\LigneVente;
use App\Models\Paiement;
use App\Models\PointDeVente;
use App\Models\Versement;
use Illuminate\Support\Carbon;

/**
 * Lecture seule, exclusivement : ce service n'écrit jamais rien, n'ouvre
 * aucune transaction, ne crée aucun Mouvement. Il ne fait qu'agréger, sur
 * une plage de dates et un point de vente donnés, des montants que le
 * moteur a déjà calculés ailleurs — jamais un calcul comptable
 * réglementaire (Charte Produit §4 : "ce n'est pas une comptabilité
 * réglementaire complète").
 */
class RapportService
{
    public function __construct(private readonly ClotureService $clotures)
    {
    }

    /**
     * @return array{
     *     point_de_vente_id: int,
     *     periode: array{debut: string, fin: string},
     *     recettes: float,
     *     depenses: float,
     *     achats: float,
     *     marge: array{montant: float, avertissement: string},
     *     benefice_estime: float,
     *     etat_caisse: array{cloture_ouverte: bool, cloture_id: ?int, especes_attendues: ?float},
     * }
     */
    public function genererRapport(PointDeVente $pointDeVente, Carbon $debut, Carbon $fin): array
    {
        $recettes = $this->recettes($pointDeVente, $debut, $fin);
        $depenses = $this->clotures->depensesSurPeriode($pointDeVente, $debut, $fin);
        $achats = $this->achats($pointDeVente, $debut, $fin);

        return [
            'point_de_vente_id' => $pointDeVente->id,
            'periode' => [
                'debut' => $debut->toDateString(),
                'fin' => $fin->toDateString(),
            ],
            'recettes' => $recettes,
            'depenses' => $depenses,
            'achats' => $achats,
            'marge' => [
                'montant' => $this->marge($pointDeVente, $debut, $fin),
                'avertissement' => 'Marge calculée avec le prix d\'achat COURANT du produit (Produit::prix_achat), pas '
                    .'son prix d\'achat au moment réel de chaque vente : le Chantier 11 ne conserve pas d\'historique du '
                    .'prix d\'achat par mouvement. Si le prix d\'achat d\'un produit a changé depuis une vente de la '
                    .'période, la marge de cette ligne est reconstituée avec un prix erroné. Corriger cela demande '
                    .'d\'historiser le prix d\'achat par mouvement de stock — un chantier séparé, pas un correctif '
                    .'improvisé ici.',
            ],
            'benefice_estime' => round($recettes - $depenses - $achats, 2),
            'etat_caisse' => $this->etatCaisse($pointDeVente),
        ];
    }

    /**
     * Recettes = paiements espèces (ventes ET acomptes de commande,
     * Chantier 14) + versements espèces sur créance (qu'elle vienne d'une
     * vente ou d'une commande) sur la période, pour ce point de vente.
     * Même filtre que ClotureService::especesAttendues() — mode especes,
     * scindé par point de vente — mais borné par une plage de dates plutôt
     * que par le statut de clôture (whereNull('cloture_id')) : un rapport
     * couvre toute la période demandée, qu'elle ait ou non déjà été
     * "avalée" par une clôture depuis.
     *
     * Limite documentée : ClotureService::especesAttendues() lui-même ne
     * couvre encore que les paiements/versements liés à une Vente — un
     * acompte de commande en espèces n'est aujourd'hui compté dans aucune
     * clôture (trou hors périmètre de ce chantier, purement lecture seule ;
     * consigné dans le Catalogue des invariants). Recettes, ici, couvre
     * volontairement aussi la commande, car un rapport doit refléter tout
     * encaissement réel, quelle que soit son origine.
     *
     * Les versements fournisseur (Chantier 11) restent exclus, pour la même
     * raison déjà documentée dans ClotureService::especesAttendues() : une
     * dette fournisseur n'a pas de lien fiable avec la caisse physique d'un
     * point de vente précis.
     */
    private function recettes(PointDeVente $pointDeVente, Carbon $debut, Carbon $fin): float
    {
        $paiements = (float) Paiement::where('mode', Paiement::MODE_ESPECES)
            ->whereBetween('created_at', [$debut, $fin])
            ->where(function ($query) use ($pointDeVente) {
                $query->whereHas('vente', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id))
                    ->orWhereHas('commande', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id));
            })
            ->sum('montant');

        $versements = (float) Versement::where('mode', Versement::MODE_ESPECES)
            ->whereBetween('created_at', [$debut, $fin])
            ->whereHas('creance', function ($query) use ($pointDeVente) {
                $query->whereHas('vente', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id))
                    ->orWhereHas('commande', fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id));
            })
            ->sum('montant');

        return round($paiements + $versements, 2);
    }

    /**
     * Achats validés sur la période. Scindé par entreprise plutôt que par
     * point de vente précis : un achat (Chantier 11) est réceptionné dans
     * un emplacement polymorphe (Depot ou PointDeVente) et n'appartient pas
     * plus particulièrement à l'un des points de vente de l'entreprise
     * qu'à un autre — même limite déjà posée pour les versements
     * fournisseur dans ClotureService::especesAttendues(). Le "Mode
     * patron" du Propriétaire porte de toute façon sur l'entreprise entière
     * pour cet agrégat précis.
     */
    private function achats(PointDeVente $pointDeVente, Carbon $debut, Carbon $fin): float
    {
        return round(
            (float) Achat::where('entreprise_id', $pointDeVente->entreprise_id)
                ->where('statut', Achat::STATUT_VALIDEE)
                ->whereBetween('created_at', [$debut, $fin])
                ->sum('montant_total'),
            2
        );
    }

    /**
     * Marge = somme, pour chaque ligne de vente de la période à ce point de
     * vente, de (prix_unitaire_ligne − prix_achat_courant_du_produit) ×
     * quantité. Voir l'avertissement retourné par genererRapport() pour la
     * limite documentée sur le prix d'achat.
     */
    private function marge(PointDeVente $pointDeVente, Carbon $debut, Carbon $fin): float
    {
        $total = LigneVente::whereHas(
            'vente',
            fn ($q) => $q->where('point_de_vente_id', $pointDeVente->id)->whereBetween('created_at', [$debut, $fin])
        )
            ->with('produit')
            ->get()
            ->sum(fn (LigneVente $ligne) => ((float) $ligne->prix_unitaire - (float) $ligne->produit->prix_achat) * (float) $ligne->quantite);

        return round($total, 2);
    }

    /**
     * Reprend ClotureService::especesAttendues() pour la clôture
     * actuellement ouverte de ce point de vente, s'il y en a une — jamais
     * recalculé, toujours à la volée comme le fait déjà ClotureService.
     * Sans clôture ouverte, il n'y a rien à rapporter ici : especes_attendues
     * reste null plutôt qu'un zéro trompeur.
     */
    private function etatCaisse(PointDeVente $pointDeVente): array
    {
        $clotureOuverte = Cloture::where('point_de_vente_id', $pointDeVente->id)
            ->where('statut', Cloture::STATUT_OUVERTE)
            ->first();

        return [
            'cloture_ouverte' => $clotureOuverte !== null,
            'cloture_id' => $clotureOuverte?->id,
            'especes_attendues' => $clotureOuverte === null ? null : $this->clotures->especesAttendues($pointDeVente),
        ];
    }
}
