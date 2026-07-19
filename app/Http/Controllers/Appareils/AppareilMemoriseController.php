<?php

namespace App\Http\Controllers\Appareils;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\AppareilMemorise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AppareilMemoriseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $appareils = $user->role === RoleEnum::Proprietaire
            ? AppareilMemorise::query()
                ->whereHas('user', fn ($query) => $query->where('entreprise_id', $user->entreprise_id))
                ->with('user:id,name')
                ->get()
            : $user->appareilsMemorises()->get();

        return Inertia::render('appareils/index', [
            'appareils' => $appareils,
        ]);
    }

    public function destroy(Request $request, AppareilMemorise $appareil): RedirectResponse
    {
        Gate::authorize('revoke', $appareil);

        $appareil->update(['revoked_at' => now()]);

        return redirect()->back();
    }
}
