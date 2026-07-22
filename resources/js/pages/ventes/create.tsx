import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEventHandler } from 'react';

type Produit = {
    id: number;
    nom: string;
    prix_vente: string;
    unite: string;
    stock_disponible: number;
};

export default function VenteCreate({ produits, abonnementSuspendu }: { produits: Produit[]; abonnementSuspendu: boolean }) {
    const [produit, setProduit] = useState<Produit | null>(null);
    const [confirme, setConfirme] = useState(false);
    const [paiementPartiel, setPaiementPartiel] = useState(false);
    const [livraisonDifferee, setLivraisonDifferee] = useState(false);

    const { data, setData, post, processing, errors, transform, reset } = useForm({
        produit_id: '',
        quantite: '1',
        montant_paye: '',
        client_nom: '',
        client_telephone: '',
        livraison_lieu: '',
        livraison_date_prevue: '',
    });

    const total = useMemo(() => {
        if (!produit) return 0;
        const quantite = parseFloat(data.quantite || '0');
        return Number(produit.prix_vente) * (Number.isNaN(quantite) ? 0 : quantite);
    }, [produit, data.quantite]);

    const besoinClient = paiementPartiel || livraisonDifferee;

    transform((formData) => ({
        ...formData,
        montant_paye: paiementPartiel ? formData.montant_paye : total,
        client_nom: besoinClient ? formData.client_nom : '',
        client_telephone: besoinClient ? formData.client_telephone : '',
        livraison_lieu: livraisonDifferee ? formData.livraison_lieu : '',
        livraison_date_prevue: livraisonDifferee ? formData.livraison_date_prevue : '',
    }));

    const choisirProduit = (p: Produit) => {
        setConfirme(false);
        setProduit(p);
        setPaiementPartiel(false);
        setLivraisonDifferee(false);
        setData('produit_id', String(p.id));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/ventes', {
            onSuccess: () => {
                setProduit(null);
                setConfirme(true);
                setPaiementPartiel(false);
                setLivraisonDifferee(false);
                reset();
            },
        });
    };

    const fieldErrors = errors as Record<string, string>;

    return (
        <>
            <Head title="Vendre" />
            <div className="min-h-screen bg-white p-6 text-black dark:bg-black dark:text-white">
                <h1 className="mb-4 text-xl font-medium">Vendre</h1>

                {confirme && <p className="mb-4 border border-green-600 p-3 text-green-700">Vente enregistrée.</p>}

                {abonnementSuspendu && (
                    <p className="mb-4 border border-red-600 p-3 text-red-700">
                        Abonnement suspendu : renouvelez-le pour reprendre les ventes.
                    </p>
                )}

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

                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={paiementPartiel} onChange={(e) => setPaiementPartiel(e.target.checked)} />
                            Le client ne paie pas tout aujourd'hui
                        </label>

                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={livraisonDifferee} onChange={(e) => setLivraisonDifferee(e.target.checked)} />
                            Remettre au client plus tard (livraison)
                        </label>

                        {paiementPartiel && (
                            <div className="flex flex-col gap-1">
                                <label htmlFor="montant_paye">Montant reçu aujourd'hui</label>
                                <input
                                    id="montant_paye"
                                    type="number"
                                    min="0"
                                    step="any"
                                    inputMode="decimal"
                                    value={data.montant_paye}
                                    onChange={(e) => setData('montant_paye', e.target.value)}
                                    className="border p-2"
                                />
                                {fieldErrors.montant_paye && <p className="text-sm text-red-600">{fieldErrors.montant_paye}</p>}
                            </div>
                        )}

                        {besoinClient && (
                            <>
                                <div className="flex flex-col gap-1">
                                    <label htmlFor="client_nom">Nom du client</label>
                                    <input
                                        id="client_nom"
                                        type="text"
                                        value={data.client_nom}
                                        onChange={(e) => setData('client_nom', e.target.value)}
                                        className="border p-2"
                                    />
                                    {fieldErrors.client_nom && <p className="text-sm text-red-600">{fieldErrors.client_nom}</p>}
                                    {fieldErrors.client && <p className="text-sm text-red-600">{fieldErrors.client}</p>}
                                </div>

                                <div className="flex flex-col gap-1">
                                    <label htmlFor="client_telephone">Téléphone du client (optionnel)</label>
                                    <input
                                        id="client_telephone"
                                        type="text"
                                        value={data.client_telephone}
                                        onChange={(e) => setData('client_telephone', e.target.value)}
                                        className="border p-2"
                                    />
                                </div>
                            </>
                        )}

                        {livraisonDifferee && (
                            <>
                                <div className="flex flex-col gap-1">
                                    <label htmlFor="livraison_lieu">Lieu de livraison</label>
                                    <input
                                        id="livraison_lieu"
                                        type="text"
                                        value={data.livraison_lieu}
                                        onChange={(e) => setData('livraison_lieu', e.target.value)}
                                        className="border p-2"
                                    />
                                    {fieldErrors.livraison_lieu && <p className="text-sm text-red-600">{fieldErrors.livraison_lieu}</p>}
                                </div>

                                <div className="flex flex-col gap-1">
                                    <label htmlFor="livraison_date_prevue">Date prévue (optionnel)</label>
                                    <input
                                        id="livraison_date_prevue"
                                        type="date"
                                        value={data.livraison_date_prevue}
                                        onChange={(e) => setData('livraison_date_prevue', e.target.value)}
                                        className="border p-2"
                                    />
                                </div>
                            </>
                        )}

                        {!abonnementSuspendu && (
                            <button type="submit" disabled={processing} className="bg-black px-4 py-2 text-white dark:bg-white dark:text-black">
                                {paiementPartiel ? 'Encaisser et enregistrer la dette' : 'Encaisser en espèces'}
                            </button>
                        )}

                        <button type="button" onClick={() => setProduit(null)} className="border px-4 py-2">
                            Annuler
                        </button>
                    </form>
                )}
            </div>
        </>
    );
}
