import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEventHandler } from 'react';

type Produit = {
    id: number;
    nom: string;
    prix_vente: string;
    unite: string;
    stock_disponible: number;
};

export default function VenteCreate({ produits }: { produits: Produit[] }) {
    const [produit, setProduit] = useState<Produit | null>(null);
    const [confirme, setConfirme] = useState(false);

    const { data, setData, post, processing, errors, transform, reset } = useForm({
        produit_id: '',
        quantite: '1',
    });

    const total = useMemo(() => {
        if (!produit) return 0;
        const quantite = parseFloat(data.quantite || '0');
        return Number(produit.prix_vente) * (Number.isNaN(quantite) ? 0 : quantite);
    }, [produit, data.quantite]);

    transform((formData) => ({
        ...formData,
        montant_paye: total,
    }));

    const choisirProduit = (p: Produit) => {
        setConfirme(false);
        setProduit(p);
        setData('produit_id', String(p.id));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/ventes', {
            onSuccess: () => {
                setProduit(null);
                setConfirme(true);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Vendre" />
            <div className="min-h-screen bg-white p-6 text-black dark:bg-black dark:text-white">
                <h1 className="mb-4 text-xl font-medium">Vendre</h1>

                {confirme && <p className="mb-4 border border-green-600 p-3 text-green-700">Vente enregistrée.</p>}

                {!produit && (
                    <ul className="flex flex-col gap-2">
                        {produits.map((p) => (
                            <li key={p.id}>
                                <button onClick={() => choisirProduit(p)} className="w-full border p-3 text-left">
                                    <span className="block font-medium">{p.nom}</span>
                                    <span className="block text-sm text-gray-500">
                                        {p.prix_vente} / {p.unite} — stock {p.stock_disponible}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}

                {produit && (
                    <form onSubmit={submit} className="flex max-w-xs flex-col gap-4">
                        <p className="font-medium">{produit.nom}</p>

                        <div className="flex flex-col gap-1">
                            <label htmlFor="quantite">Quantité ({produit.unite})</label>
                            <input
                                id="quantite"
                                type="number"
                                min="0"
                                step="any"
                                inputMode="decimal"
                                value={data.quantite}
                                onChange={(e) => setData('quantite', e.target.value)}
                                className="border p-2"
                            />
                            {errors.quantite && <p className="text-sm text-red-600">{errors.quantite}</p>}
                        </div>

                        <p className="text-lg font-medium">Total : {total}</p>
                        {(errors as Record<string, string>).montant_paye && (
                            <p className="text-sm text-red-600">{(errors as Record<string, string>).montant_paye}</p>
                        )}

                        <button type="submit" disabled={processing} className="bg-black px-4 py-2 text-white dark:bg-white dark:text-black">
                            Encaisser en espèces
                        </button>

                        <button type="button" onClick={() => setProduit(null)} className="border px-4 py-2">
                            Annuler
                        </button>
                    </form>
                )}
            </div>
        </>
    );
}
