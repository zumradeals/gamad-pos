import { cn } from '@/lib/utils';

/**
 * Surface blanche (--color-surface) qui se détache du fond crème
 * (--color-background) — bordure fine plutôt qu'une ombre marquée, cohérent
 * avec "priorité absolue à la clarté sur toute fioriture".
 */
export function Card({ className, children }: { className?: string; children: React.ReactNode }) {
    return <div className={cn('rounded-xl border border-border bg-surface p-4', className)}>{children}</div>;
}
