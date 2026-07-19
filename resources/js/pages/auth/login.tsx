import { Head, useForm } from '@inertiajs/react';
import { useEffect, type FormEventHandler } from 'react';

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
            <div className="flex min-h-screen items-center justify-center bg-white text-black dark:bg-black dark:text-white">
                <form onSubmit={submit} className="flex w-full max-w-xs flex-col gap-4">
                    <h1 className="text-xl font-medium">GAMAD POS — Connexion</h1>

                    <div className="flex flex-col gap-1">
                        <label htmlFor="telephone">Téléphone</label>
                        <input
                            id="telephone"
                            type="tel"
                            autoComplete="tel"
                            value={data.telephone}
                            onChange={(e) => setData('telephone', e.target.value)}
                            className="border p-2"
                        />
                        {errors.telephone && <p className="text-sm text-red-600">{errors.telephone}</p>}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label htmlFor="pin">Code PIN</label>
                        <input
                            id="pin"
                            type="password"
                            inputMode="numeric"
                            autoComplete="current-password"
                            value={data.pin}
                            onChange={(e) => setData('pin', e.target.value)}
                            className="border p-2"
                        />
                        {errors.pin && <p className="text-sm text-red-600">{errors.pin}</p>}
                    </div>

                    <button type="submit" disabled={processing} className="bg-black px-4 py-2 text-white dark:bg-white dark:text-black">
                        Se connecter
                    </button>
                </form>
            </div>
        </>
    );
}
