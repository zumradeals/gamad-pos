import { cn } from '@/lib/utils';
import { forwardRef } from 'react';

/**
 * Cible tactile ≥ 44px (h-11). Le focus utilise le bleu (--color-primary,
 * "confiance/structure") plutôt que l'ambre — l'ambre reste réservé au seul
 * CTA de l'écran, jamais répété sur chaque champ.
 */
export const Input = forwardRef<HTMLInputElement, React.InputHTMLAttributes<HTMLInputElement>>(function Input(
    { className, ...props },
    ref,
) {
    return (
        <input
            ref={ref}
            className={cn(
                'h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-foreground placeholder:text-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
});
