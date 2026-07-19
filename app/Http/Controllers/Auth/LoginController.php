<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    public function store(Request $request, AuthenticationService $authentication): RedirectResponse
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'pin' => ['required', 'string'],
            'device_id' => ['nullable', 'string'],
        ]);

        $resultat = $authentication->tenterConnexion(
            telephone: $data['telephone'],
            pin: $data['pin'],
            deviceId: $data['device_id'] ?? null,
            ip: $request->ip(),
        );

        if (! $resultat->succes) {
            throw ValidationException::withMessages([
                'pin' => 'Numéro de téléphone ou code PIN incorrect.',
            ]);
        }

        $request->session()->regenerate();

        $pointsDeVente = $resultat->user->pointsDeVente()->get();

        if ($pointsDeVente->count() === 1) {
            $request->session()->put('point_de_vente_id', $pointsDeVente->first()->id);

            return redirect()->route('home');
        }

        if ($pointsDeVente->count() > 1) {
            return redirect()->route('points-de-vente.selection');
        }

        return redirect()->route('home');
    }
}
