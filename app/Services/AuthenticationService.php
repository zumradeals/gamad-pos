<?php

namespace App\Services;

use App\Models\AppareilMemorise;
use App\Models\JournalConnexion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticationService
{
    /**
     * Attempt a phone + PIN login. Always writes an entry to journal_connexions,
     * whether the attempt succeeds or fails.
     */
    public function tenterConnexion(
        string $telephone,
        string $pin,
        ?string $deviceId,
        ?string $ip,
    ): AuthenticationAttemptResult {
        $user = User::where('telephone', $telephone)->first();

        if (! $user || ! $user->pin || ! Hash::check($pin, $user->pin)) {
            $this->journaliser($user, $telephone, null, JournalConnexion::RESULTAT_ECHEC, $ip);

            return new AuthenticationAttemptResult(false, null, null);
        }

        Auth::login($user);

        $appareil = $deviceId ? $this->memoriserAppareil($user, $deviceId) : null;

        $this->journaliser($user, $telephone, $appareil, JournalConnexion::RESULTAT_SUCCES, $ip);

        return new AuthenticationAttemptResult(true, $user, $appareil);
    }

    /**
     * Create or refresh the remembered-device record for this user, returning
     * a new plaintext device token (only ever exposed once, at creation time).
     */
    private function memoriserAppareil(User $user, string $deviceId): AppareilMemorise
    {
        $tokenEnClair = Str::random(60);

        $appareil = AppareilMemorise::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            ['token' => $tokenEnClair, 'memorized_at' => now(), 'revoked_at' => null],
        );

        $appareil->setAttribute('tokenEnClair', $tokenEnClair);

        return $appareil;
    }

    private function journaliser(
        ?User $user,
        string $telephone,
        ?AppareilMemorise $appareil,
        string $resultat,
        ?string $ip,
    ): void {
        JournalConnexion::create([
            'user_id' => $user?->id,
            'telephone' => $telephone,
            'appareil_memorise_id' => $appareil?->id,
            'resultat' => $resultat,
            'ip' => $ip,
        ]);
    }
}
