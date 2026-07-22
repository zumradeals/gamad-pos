import { Head, router } from '@inertiajs/react';
import { Card } from '@/components/ui/card';

type PointDeVente = {
    id: number;
    nom: string;
    adresse: string | null;
};

export default function Selection({
    pointsDeVente,
}: {
    pointsDeVente: PointDeVente[];
}) {
    const selectionner = (id: number) => {
        router.post('/points-de-vente/selection', { point_de_vente_id: id });
    };

    return (
        <>
            <Head title="Sélection du point de vente" />
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-background p-4">
                <h1 className="font-display text-xl font-semibold text-foreground">
                    Choisissez un point de vente
                </h1>
                <div className="flex w-full max-w-sm flex-col gap-3">
                    {pointsDeVente.map((pdv) => (
                        <Card key={pdv.id} className="p-0">
                            <button
                                onClick={() => selectionner(pdv.id)}
                                className="flex w-full flex-col gap-0.5 p-4 text-left"
                            >
                                <span className="text-base font-medium text-foreground">
                                    {pdv.nom}
                                </span>
                                {pdv.adresse && (
                                    <span className="text-sm text-muted-foreground">
                                        {pdv.adresse}
                                    </span>
                                )}
                            </button>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
