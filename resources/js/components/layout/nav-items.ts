/**
 * Liste volontairement courte : seuls les écrans qui existent réellement
 * aujourd'hui (Chantier 17 ne construit aucun nouvel écran). D'autres routes
 * backend existent déjà (clôtures, dépenses, rapports...) mais n'ont encore
 * aucune page Inertia dédiée — les y renvoyer casserait la navigation.
 * Chaque futur chantier d'écran ajoute sa propre entrée ici. La navigation
 * n'est qu'un raccourci visuel : elle ne remplace jamais le contrôle
 * d'accès côté moteur (invariant G1), déjà assuré indépendamment par les
 * routes/policies existantes.
 */
export type NavItem = {
    href: string;
    label: string;
    roles: string[];
};

export const NAV_ITEMS: NavItem[] = [
    { href: '/ventes', label: 'Vendre', roles: ['vendeur'] },
    {
        href: '/livraisons',
        label: 'Livraisons',
        roles: ['livreur', 'proprietaire', 'caissier', 'magasinier'],
    },
    {
        href: '/appareils',
        label: 'Appareils',
        roles: ['vendeur', 'livreur', 'proprietaire', 'caissier', 'magasinier'],
    },
];

export function navItemsForRole(role: string | null | undefined): NavItem[] {
    return NAV_ITEMS.filter((item) => role && item.roles.includes(role));
}
