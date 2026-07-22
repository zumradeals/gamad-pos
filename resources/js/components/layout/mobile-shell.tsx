import { Link, router, usePage } from '@inertiajs/react';
import { navItemsForRole } from './nav-items';

/**
 * Vendeur/Livreur (Charte Produit §5.6) : une colonne étroite même sur un
 * écran large (max-w-md), une seule action principale par écran, navigation
 * en barre basse à cibles larges — pensé mobile-first, jamais un simple
 * rétrécissement d'un gabarit desk.
 */
export function MobileShell({
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
        <div className="flex min-h-screen flex-col bg-background">
            <header className="flex h-14 shrink-0 items-center justify-between bg-primary px-4 text-primary-foreground">
                <span className="font-display text-lg font-semibold">
                    {title}
                </span>
                <button
                    type="button"
                    onClick={() => router.post('/logout')}
                    className="rounded-lg px-3 py-2 text-sm font-medium text-primary-foreground/90 hover:bg-white/10"
                >
                    Quitter
                </button>
            </header>

            <main className="mx-auto flex w-full max-w-md flex-1 flex-col gap-4 p-4 pb-24">
                {children}
            </main>

            {items.length > 0 && (
                <nav className="fixed inset-x-0 bottom-0 border-t border-border bg-surface">
                    <div className="mx-auto flex w-full max-w-md">
                        {items.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="flex h-14 flex-1 items-center justify-center text-sm font-medium text-foreground hover:bg-background"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </div>
                </nav>
            )}
        </div>
    );
}
