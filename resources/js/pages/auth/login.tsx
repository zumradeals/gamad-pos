import { Head, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import type { FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        telephone: '',
        pin: '',
        device_id: '',
    });

    useEffect(() => {
        let deviceId = localStorage.getItem('gamad_device_id');

        if (!deviceId) {
            deviceId = crypto.randomUUID();
            localStorage.setItem('gamad_device_id', deviceId);
        }

        setData('device_id', deviceId);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Connexion" />
            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <Card className="w-full max-w-xs">
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <h1 className="font-display text-xl font-semibold text-foreground">
                            GAMAD POS
                        </h1>

                        <FormField
                            label="Téléphone"
                            htmlFor="telephone"
                            error={errors.telephone}
                        >
                            <Input
                                id="telephone"
                                type="tel"
                                autoComplete="tel"
                                value={data.telephone}
                                onChange={(e) =>
                                    setData('telephone', e.target.value)
                                }
                            />
                        </FormField>

                        <FormField
                            label="Code PIN"
                            htmlFor="pin"
                            error={errors.pin}
                        >
                            <Input
                                id="pin"
                                type="password"
                                inputMode="numeric"
                                autoComplete="current-password"
                                value={data.pin}
                                onChange={(e) => setData('pin', e.target.value)}
                            />
                        </FormField>

                        <Button
                            type="submit"
                            loading={processing}
                            className="w-full"
                        >
                            Se connecter
                        </Button>
                    </form>
                </Card>
            </div>
        </>
    );
}
