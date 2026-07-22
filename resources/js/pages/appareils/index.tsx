import { Head, router } from '@inertiajs/react';
import { AppShell } from '@/components/layout/app-shell';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

type Appareil = {
    id: number;
    device_id: string;
    memorized_at: string;
    revoked_at: string | null;
    user?: { id: number; name: string };
};

export default function AppareilsIndex({
    appareils,
}: {
    appareils: Appareil[];
}) {
    const revoquer = (id: number) => {
        router.delete(`/appareils/${id}`);
    };

    return (
        <AppShell title="Appareils mémorisés">
            <Head title="Appareils mémorisés" />

            <div className="flex flex-col gap-3">
                {appareils.map((appareil) => (
                    <Card
                        key={appareil.id}
                        className="flex flex-wrap items-center justify-between gap-3"
                    >
                        <span className="text-sm text-foreground">
                            {appareil.device_id}
                            {appareil.user ? ` — ${appareil.user.name}` : ''}
                            {appareil.revoked_at ? ' (révoqué)' : ''}
                        </span>
                        {!appareil.revoked_at && (
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => revoquer(appareil.id)}
                            >
                                Révoquer
                            </Button>
                        )}
                    </Card>
                ))}
            </div>
        </AppShell>
    );
}
