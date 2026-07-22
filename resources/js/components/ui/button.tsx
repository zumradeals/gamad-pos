import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import { forwardRef } from 'react';
import { Spinner } from './spinner';

/**
 * Trois variantes seulement, à dessein : "primary" est le SEUL accent ambre
 * autorisé par écran (discipline "un accent par vue", Charte Produit §5) —
 * jamais utilisé pour une action secondaire ou d'annulation. "secondary" et
 * "ghost" restent neutres pour cette raison, jamais bleu ni ambre.
 * Cible tactile ≥ 44px (h-11) par défaut ; "sm" n'existe que pour les
 * contextes desk denses (tableaux) où la cible reste accessible à la souris.
 */
const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 rounded-lg text-base font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                primary: 'bg-accent text-accent-foreground hover:bg-accent/90',
                secondary: 'border border-border bg-surface text-foreground hover:bg-background',
                ghost: 'text-foreground hover:bg-foreground/5',
            },
            size: {
                default: 'h-11 px-5',
                sm: 'h-9 px-3 text-sm',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'default',
        },
    },
);

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof buttonVariants> & {
        loading?: boolean;
    };

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    { className, variant, size, loading = false, disabled, children, ...props },
    ref,
) {
    return (
        <button ref={ref} className={cn(buttonVariants({ variant, size }), className)} disabled={disabled || loading} {...props}>
            {loading && <Spinner />}
            {children}
        </button>
    );
});
