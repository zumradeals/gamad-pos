<?php

namespace App\Http\Controllers\Clotures;

use App\Http\Controllers\Controller;
use App\Models\Cloture;
use App\Services\ClotureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClotureController extends Controller
{
    /**
     * Minimal endpoint sufficient to exercise validation end-to-end — reuses
     * ClotureService from Chantier 7 unmodified.
     */
    public function valider(Request $request, Cloture $cloture, ClotureService $clotures): RedirectResponse
    {
        $data = $request->validate([
            'especes_comptees' => ['required', 'numeric', 'min:0'],
        ]);

        $clotures->valider($cloture, (float) $data['especes_comptees'], $request->user());

        return redirect()->back();
    }
}
