<?php

namespace Database\Seeders;

use App\Models\Offre;
use Illuminate\Database\Seeder;

/**
 * Fixed catalogue (§7.1 roadmap) — five rows, no dynamic management screen
 * at this stage.
 */
class OffreSeeder extends Seeder
{
    public function run(): void
    {
        $offres = [
            [
                'code' => Offre::DECOUVERTE,
                'nom' => 'Découverte',
                'description' => 'Durée ou capacités limitées — une entreprise, un point de vente, un utilisateur, fonctions essentielles.',
                'limite_points_de_vente' => 1,
                'limite_utilisateurs' => 1,
            ],
            [
                'code' => Offre::SOLO,
                'nom' => 'Solo',
                'description' => 'Un point de vente, quelques utilisateurs — ventes, stock, créances, livraisons, assistance standard.',
                'limite_points_de_vente' => 1,
                'limite_utilisateurs' => 3,
            ],
            [
                'code' => Offre::COMMERCE,
                'nom' => 'Commerce',
                'description' => 'Plusieurs utilisateurs, gestion des rôles, rapports, dépenses, fournisseurs, inventaires, sauvegarde renforcée.',
                'limite_points_de_vente' => 1,
                'limite_utilisateurs' => 10,
            ],
            [
                'code' => Offre::RESEAU,
                'nom' => 'Réseau',
                'description' => 'Plusieurs points de vente, plusieurs dépôts, transferts, consolidation, supervision, permissions avancées.',
                'limite_points_de_vente' => 5,
                'limite_utilisateurs' => 25,
            ],
            [
                'code' => Offre::ENTREPRISE,
                'nom' => 'Entreprise',
                'description' => 'Modules, intégrations, API, accompagnement, personnalisation, contrats spécifiques.',
                'limite_points_de_vente' => null,
                'limite_utilisateurs' => null,
            ],
        ];

        foreach ($offres as $offre) {
            Offre::updateOrCreate(['code' => $offre['code']], $offre);
        }
    }
}
