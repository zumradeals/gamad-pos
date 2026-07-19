import { Head, router } from '@inertiajs/react';

type Appareil = {
    id: number;
    device_id: string;
    memorized_at: string;
    revoked_at: string | null;
    user?: { id: number; name: string };
};

export default function AppareilsIndex({ appareils }: { appareils: Appareil[] }) {
    const revoquer = (id: number) => {
        router.delete(`/appareils/${id}`);
    };

    return (
        <>
            <Head title="Appareils mémorisés" />
            <div className="min-h-screen bg-white p-8 text-black dark:bg-black dark:text-white">
                <h1 className="mb-4 text-xl font-medium">Appareils mémorisés</h1>
                <ul className="flex flex-col gap-2">
                    {appareils.map((appareil) => (
                        <li key={appareil.id} className="flex items-center justify-between gap-4 border p-3">
                            <span>
                                {appareil.device_id}
                                {appareil.user ? ` — ${appareil.user.name}` : ''}
                                {appareil.revoked_at ? ' (révoqué)' : ''}
                            </span>
                            {!appareil.revoked_at && (
                                <button onClick={() => revoquer(appareil.id)} className="border px-3 py-1">
                                    Révoquer
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}
