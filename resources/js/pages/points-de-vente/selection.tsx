import { Head, router } from '@inertiajs/react';

type PointDeVente = {
    id: number;
    nom: string;
    adresse: string | null;
};

export default function Selection({ pointsDeVente }: { pointsDeVente: PointDeVente[] }) {
    const selectionner = (id: number) => {
        router.post('/points-de-vente/selection', { point_de_vente_id: id });
    };

    return (
        <>
            <Head title="Sélection du point de vente" />
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-white text-black dark:bg-black dark:text-white">
                <h1 className="text-xl font-medium">Choisissez un point de vente</h1>
                <ul className="flex w-full max-w-sm flex-col gap-2">
                    {pointsDeVente.map((pdv) => (
                        <li key={pdv.id}>
                            <button onClick={() => selectionner(pdv.id)} className="w-full border p-3 text-left">
                                <span className="block font-medium">{pdv.nom}</span>
                                {pdv.adresse && <span className="block text-sm text-gray-500">{pdv.adresse}</span>}
                            </button>
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}
