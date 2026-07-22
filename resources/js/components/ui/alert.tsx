import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';

/**
 * "info" réutilise le bleu (--color-primary) plutôt qu'une quatrième couleur
 * : la palette le décrit déjà comme la couleur de "confiance/structure", ce
 * qui correspond exactement à un message informatif neutre. Le vert reste
 * strictement borné à "success" (confirmations, états positifs), jamais
 * réutilisé ici.
 */
const alertVariants = cva('rounded-lg border p-3 text-sm', {
    variants: {
        variant: {
            success: 'border-success/20 bg-success-soft text-success-foreground',
            error: 'border-danger/20 bg-danger-soft text-danger-foreground',
            info: 'border-primary/20 bg-primary/10 text-primary',
        },
    },
    defaultVariants: {
        variant: 'info',
    },
});

type AlertProps = React.HTMLAttributes<HTMLDivElement> & VariantProps<typeof alertVariants>;

export function Alert({ className, variant, children, ...props }: AlertProps) {
    return (
        <div role={variant === 'error' ? 'alert' : 'status'} className={cn(alertVariants({ variant }), className)} {...props}>
            {children}
        </div>
    );
}
