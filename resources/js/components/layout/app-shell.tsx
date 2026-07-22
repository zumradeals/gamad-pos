import { usePage } from '@inertiajs/react';
import { DeskShell } from './desk-shell';
import { MobileShell } from './mobile-shell';

const ROLES_MOBILE = new Set(['vendeur', 'livreur']);

/**
 * "Navigation adaptée au rôle connecté" (Chantier 17) : un même écran
 * (livraisons, appareils) peut être visité par un rôle mobile-first ou un
 * rôle desk-first (Charte Produit §5.6). Plutôt que de dupliquer chaque
 * page pour choisir elle-même son gabarit, ce point d'entrée unique lit le
 * rôle de l'utilisateur connecté (props Inertia partagées) et choisit une
 * bonne fois pour toutes — jamais laissé à l'intuition du moment ni décidé
 * indépendamment par chaque écran.
 */
export function AppShell({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;
    const role = auth.user.role;

    if (role && ROLES_MOBILE.has(role)) {
        return <MobileShell title={title}>{children}</MobileShell>;
    }

    return <DeskShell title={title}>{children}</DeskShell>;
}
