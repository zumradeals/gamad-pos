import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="GAMAD POS" />
            <div className="flex min-h-screen items-center justify-center bg-white text-black dark:bg-black dark:text-white">
                <h1 className="text-2xl font-medium">GAMAD POS — squelette OK</h1>
            </div>
        </>
    );
}
