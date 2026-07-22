import { cn } from '@/lib/utils';

/**
 * Indicateur de chargement minimal : une simple rotation, jamais
 * décorative — signale un état, ne divertit pas (Charte Produit §5).
 */
export function Spinner({ className }: { className?: string }) {
    return (
        <span
            role="status"
            aria-label="Chargement"
            className={cn('inline-block size-4 animate-spin rounded-full border-2 border-current border-t-transparent', className)}
        />
    );
}
