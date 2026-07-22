import { Link, router, usePage } from '@inertiajs/react';
import { navItemsForRole } from './nav-items';

/**
 * Propriétaire/Caissier/Magasinier (Charte Produit §5.6) : poste fixe
 * privilégié, plus dense, contenu large (tableaux/listes lisibles) — mais
 * reste utilisable sur mobile (la nav passe en lignes qui s'enroulent
 * plutôt que de disparaître derrière un menu cachant l'accès).
 */
export function DeskShell({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;
    const role = auth.user.role;
    const items = navItemsForRole(role);

    return (
        <div data-shell="desktop" className="min-h-screen bg-background">
            <header className="border-b border-border bg-primary text-primary-foreground">
                <div className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div className="flex flex-wrap items-center gap-6">
                        <span className="font-display text-xl font-semibold">
                            {title}
                        </span>
                        {items.length > 0 && (
                            <nav className="flex flex-wrap items-center gap-1">
                                {items.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className="rounded-lg px-3 py-2 text-sm font-medium text-primary-foreground/90 hover:bg-white/10"
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                            </nav>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        <span className="text-sm text-primary-foreground/80">
                            {auth.user.name}
                        </span>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="rounded-lg px-3 py-2 text-sm font-medium text-primary-foreground/90 hover:bg-white/10"
                        >
                            Se déconnecter
                        </button>
                    </div>
                </div>
            </header>

            <main
                data-shell-main
                className="mx-auto w-full max-w-5xl px-6 py-6"
            >
                <div className="flex flex-col gap-4">{children}</div>
            </main>
        </div>
    );
}
