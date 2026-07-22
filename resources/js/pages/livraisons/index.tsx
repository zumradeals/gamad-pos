import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';
import { AppShell } from '@/components/layout/app-shell';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Livraison = {
    id: number;
    lieu: string;
    statut: string;
    client: string;
    reste_a_livrer: number;
    responsable_id: number | null;
    responsable_nom: string | null;
};

type Livreur = {
    id: number;
    name: string;
};

export default function LivraisonsIndex({
    livraisons,
    livreurs,
    peutAssigner,
    abonnementSuspendu,
}: {
    livraisons: Livraison[];
    livreurs: Livreur[];
    peutAssigner: boolean;
    abonnementSuspendu: boolean;
}) {
    const [enCours, setEnCours] = useState<number | null>(null);

    const assignerResponsable = (
        livraisonId: number,
        responsableUserId: string,
    ) => {
        if (!responsableUserId) {
            return;
        }

        router.patch(`/livraisons/${livraisonId}/responsable`, {
            responsable_user_id: responsableUserId,
        });
    };

    const { data, setData, post, processing, errors, reset } = useForm({
        quantite: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (enCours === null) {
            return;
        }

        post(`/livraisons/${enCours}/lignes`, {
            onSuccess: () => {
                setEnCours(null);
                reset();
            },
        });
    };

    return (
        <AppShell title="Livraisons">
            <Head title="Livraisons" />

            {abonnementSuspendu && (
                <Alert variant="error">
                    Abonnement suspendu : renouvelez-le pour reprendre les
                    livraisons.
                </Alert>
            )}

            <div className="flex flex-col gap-3">
                {livraisons.map((livraison) => (
                    <Card key={livraison.id} className="flex flex-col gap-3">
                        <div>
                            <p className="text-base font-medium text-foreground">
                                {livraison.client} — {livraison.lieu}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {livraison.statut} — reste à livrer :{' '}
                                {livraison.reste_a_livrer}
                                {livraison.responsable_nom
                                    ? ` — responsable : ${livraison.responsable_nom}`
                                    : ''}
                            </p>
                        </div>

                        {peutAssigner && livraison.statut !== 'livree' && (
                            <label className="flex flex-col gap-1.5 text-sm font-medium text-foreground sm:flex-row sm:items-center sm:gap-3">
                                Responsable
                                <select
                                    value={livraison.responsable_id ?? ''}
                                    onChange={(e) =>
                                        assignerResponsable(
                                            livraison.id,
                                            e.target.value,
                                        )
                                    }
                                    className="h-11 rounded-lg border border-border bg-surface px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none"
                                >
                                    <option value="" disabled>
                                        Choisir un livreur
                                    </option>
                                    {livreurs.map((livreur) => (
                                        <option
                                            key={livreur.id}
                                            value={livreur.id}
                                        >
                                            {livreur.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        )}

                        {!abonnementSuspendu &&
                            livraison.statut !== 'livree' &&
                            (enCours === livraison.id ? (
                                <form
                                    onSubmit={submit}
                                    className="flex flex-wrap items-center gap-2"
                                >
                                    <Input
                                        type="number"
                                        min="0"
                                        step="any"
                                        inputMode="decimal"
                                        value={data.quantite}
                                        onChange={(e) =>
                                            setData('quantite', e.target.value)
                                        }
                                        className="w-32"
                                        autoFocus
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        loading={processing}
                                    >
                                        Confirmer
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => setEnCours(null)}
                                    >
                                        Annuler
                                    </Button>
                                    {errors.quantite && (
                                        <p className="w-full text-sm text-danger">
                                            {errors.quantite}
                                        </p>
                                    )}
                                </form>
                            ) : (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    className="w-fit"
                                    onClick={() => setEnCours(livraison.id)}
                                >
                                    Marquer comme livrée
                                </Button>
                            ))}
                    </Card>
                ))}
            </div>
        </AppShell>
    );
}
