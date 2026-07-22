import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEventHandler } from 'react';
import { AppShell } from '@/components/layout/app-shell';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';

type Produit = {
    id: number;
    nom: string;
    prix_vente: string;
    unite: string;
    stock_disponible: number;
};

export default function VenteCreate({
    produits,
    abonnementSuspendu,
}: {
    produits: Produit[];
    abonnementSuspendu: boolean;
}) {
    const [produit, setProduit] = useState<Produit | null>(null);
    const [confirme, setConfirme] = useState(false);
    const [paiementPartiel, setPaiementPartiel] = useState(false);
    const [livraisonDifferee, setLivraisonDifferee] = useState(false);

    const { data, setData, post, processing, errors, transform, reset } =
        useForm({
            produit_id: '',
            quantite: '1',
            montant_paye: '',
            client_nom: '',
            client_telephone: '',
            livraison_lieu: '',
            livraison_date_prevue: '',
        });

    const total = useMemo(() => {
        if (!produit) {
            return 0;
        }

        const quantite = parseFloat(data.quantite || '0');

        return (
            Number(produit.prix_vente) * (Number.isNaN(quantite) ? 0 : quantite)
        );
    }, [produit, data.quantite]);

    const besoinClient = paiementPartiel || livraisonDifferee;

    transform((formData) => ({
        ...formData,
        montant_paye: paiementPartiel ? formData.montant_paye : total,
        client_nom: besoinClient ? formData.client_nom : '',
        client_telephone: besoinClient ? formData.client_telephone : '',
        livraison_lieu: livraisonDifferee ? formData.livraison_lieu : '',
        livraison_date_prevue: livraisonDifferee
            ? formData.livraison_date_prevue
            : '',
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
        <AppShell title="Vendre">
            <Head title="Vendre" />

            {confirme && <Alert variant="success">Vente enregistrée.</Alert>}

            {abonnementSuspendu && (
                <Alert variant="error">
                    Abonnement suspendu : renouvelez-le pour reprendre les
                    ventes.
                </Alert>
            )}

            {!produit && (
                <div className="flex flex-col gap-3">
                    {produits.map((p) => (
                        <Card key={p.id} className="p-0">
                            <button
                                onClick={() => choisirProduit(p)}
                                className="flex w-full flex-col gap-1 p-4 text-left"
                            >
                                <span className="text-base font-medium text-foreground">
                                    {p.nom}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {p.prix_vente} / {p.unite} — stock{' '}
                                    {p.stock_disponible}
                                </span>
                            </button>
                        </Card>
                    ))}
                </div>
            )}

            {produit && (
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <p className="text-base font-medium text-foreground">
                        {produit.nom}
                    </p>

                    <FormField
                        label={`Quantité (${produit.unite})`}
                        htmlFor="quantite"
                        error={errors.quantite}
                    >
                        <Input
                            id="quantite"
                            type="number"
                            min="0"
                            step="any"
                            inputMode="decimal"
                            value={data.quantite}
                            onChange={(e) =>
                                setData('quantite', e.target.value)
                            }
                        />
                    </FormField>

                    <p className="font-display text-lg font-semibold text-foreground">
                        Total : {total}
                    </p>

                    <label className="flex min-h-11 items-center gap-3 text-sm text-foreground">
                        <input
                            type="checkbox"
                            checked={paiementPartiel}
                            onChange={(e) =>
                                setPaiementPartiel(e.target.checked)
                            }
                            className="size-5 accent-primary"
                        />
                        Le client ne paie pas tout aujourd'hui
                    </label>

                    <label className="flex min-h-11 items-center gap-3 text-sm text-foreground">
                        <input
                            type="checkbox"
                            checked={livraisonDifferee}
                            onChange={(e) =>
                                setLivraisonDifferee(e.target.checked)
                            }
                            className="size-5 accent-primary"
                        />
                        Remettre au client plus tard (livraison)
                    </label>

                    {paiementPartiel && (
                        <FormField
                            label="Montant reçu aujourd'hui"
                            htmlFor="montant_paye"
                            error={fieldErrors.montant_paye}
                        >
                            <Input
                                id="montant_paye"
                                type="number"
                                min="0"
                                step="any"
                                inputMode="decimal"
                                value={data.montant_paye}
                                onChange={(e) =>
                                    setData('montant_paye', e.target.value)
                                }
                            />
                        </FormField>
                    )}

                    {besoinClient && (
                        <>
                            <FormField
                                label="Nom du client"
                                htmlFor="client_nom"
                                error={
                                    fieldErrors.client_nom ?? fieldErrors.client
                                }
                            >
                                <Input
                                    id="client_nom"
                                    type="text"
                                    value={data.client_nom}
                                    onChange={(e) =>
                                        setData('client_nom', e.target.value)
                                    }
                                />
                            </FormField>

                            <FormField
                                label="Téléphone du client (optionnel)"
                                htmlFor="client_telephone"
                            >
                                <Input
                                    id="client_telephone"
                                    type="text"
                                    value={data.client_telephone}
                                    onChange={(e) =>
                                        setData(
                                            'client_telephone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </FormField>
                        </>
                    )}

                    {livraisonDifferee && (
                        <>
                            <FormField
                                label="Lieu de livraison"
                                htmlFor="livraison_lieu"
                                error={fieldErrors.livraison_lieu}
                            >
                                <Input
                                    id="livraison_lieu"
                                    type="text"
                                    value={data.livraison_lieu}
                                    onChange={(e) =>
                                        setData(
                                            'livraison_lieu',
                                            e.target.value,
                                        )
                                    }
                                />
                            </FormField>

                            <FormField
                                label="Date prévue (optionnel)"
                                htmlFor="livraison_date_prevue"
                            >
                                <Input
                                    id="livraison_date_prevue"
                                    type="date"
                                    value={data.livraison_date_prevue}
                                    onChange={(e) =>
                                        setData(
                                            'livraison_date_prevue',
                                            e.target.value,
                                        )
                                    }
                                />
                            </FormField>
                        </>
                    )}

                    {!abonnementSuspendu && (
                        <Button
                            type="submit"
                            loading={processing}
                            className="w-full"
                        >
                            {paiementPartiel
                                ? 'Encaisser et enregistrer la dette'
                                : 'Encaisser en espèces'}
                        </Button>
                    )}

                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => setProduit(null)}
                        className="w-full"
                    >
                        Annuler
                    </Button>
                </form>
            )}
        </AppShell>
    );
}
