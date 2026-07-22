import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';

type Livraison = {
    id: number;
    lieu: string;
    statut: string;
    client: string;
    reste_a_livrer: number;
};

export default function LivraisonsIndex({ livraisons }: { livraisons: Livraison[] }) {
    const [enCours, setEnCours] = useState<number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        quantite: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (enCours === null) return;

        post(`/livraisons/${enCours}/lignes`, {
            onSuccess: () => {
                setEnCours(null);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Livraisons" />
            <div className="min-h-screen bg-white p-8 text-black dark:bg-black dark:text-white">
                <h1 className="mb-4 text-xl font-medium">Livraisons</h1>
                <ul className="flex flex-col gap-2">
                    {livraisons.map((livraison) => (
                        <li key={livraison.id} className="flex flex-col gap-2 border p-3">
                            <span>
                                {livraison.client} — {livraison.lieu} ({livraison.statut}) — reste à livrer : {livraison.reste_a_livrer}
                            </span>

                            {livraison.statut !== 'livree' &&
                                (enCours === livraison.id ? (
                                    <form onSubmit={submit} className="flex items-center gap-2">
                                        <input
                                            type="number"
                                            min="0"
                                            step="any"
                                            inputMode="decimal"
                                            value={data.quantite}
                                            onChange={(e) => setData('quantite', e.target.value)}
                                            className="border p-1"
                                            autoFocus
                                        />
                                        <button type="submit" disabled={processing} className="border px-3 py-1">
                                            Confirmer
                                        </button>
                                        <button type="button" onClick={() => setEnCours(null)} className="border px-3 py-1">
                                            Annuler
                                        </button>
                                        {errors.quantite && <p className="text-sm text-red-600">{errors.quantite}</p>}
                                    </form>
                                ) : (
                                    <button onClick={() => setEnCours(livraison.id)} className="w-fit border px-3 py-1">
                                        Marquer comme livrée
                                    </button>
                                ))}
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}
