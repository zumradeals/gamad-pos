<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\JournalConnexion;
use App\Models\PointDeVente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_by_phone_and_pin_creates_a_journal_entry_and_memorizes_the_device(): void
    {
        $entreprise = Entreprise::factory()->create();

        $user = User::factory()
            ->pourEntreprise($entreprise, RoleEnum::Proprietaire)
            ->create([
                'telephone' => '+237699112233',
                'pin' => '4242',
            ]);

        $response = $this->post('/login', [
            'telephone' => '+237699112233',
            'pin' => '4242',
            'device_id' => 'test-device-abc',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('journal_connexions', [
            'user_id' => $user->id,
            'telephone' => '+237699112233',
            'resultat' => JournalConnexion::RESULTAT_SUCCES,
        ]);

        $this->assertDatabaseHas('appareils_memorises', [
            'user_id' => $user->id,
            'device_id' => 'test-device-abc',
        ]);

        $appareil = $user->appareilsMemorises()->first();
        $this->assertNotNull($appareil);
        $this->assertNull($appareil->revoked_at);

        $journal = JournalConnexion::query()->where('user_id', $user->id)->first();
        $this->assertSame($appareil->id, $journal->appareil_memorise_id);
    }

    public function test_login_redirects_to_point_de_vente_selection_when_user_has_several(): void
    {
        $entreprise = Entreprise::factory()->create();
        $pointsDeVente = PointDeVente::factory()->count(2)->for($entreprise)->create();

        $user = User::factory()
            ->pourEntreprise($entreprise, RoleEnum::Vendeur)
            ->create([
                'telephone' => '+237699445566',
                'pin' => '1357',
            ]);

        $user->pointsDeVente()->attach($pointsDeVente->pluck('id'));

        $response = $this->post('/login', [
            'telephone' => '+237699445566',
            'pin' => '1357',
        ]);

        $response->assertRedirect(route('points-de-vente.selection'));
    }

    public function test_failed_login_with_wrong_pin_logs_a_failure_and_refuses_access(): void
    {
        $entreprise = Entreprise::factory()->create();

        User::factory()
            ->pourEntreprise($entreprise, RoleEnum::Vendeur)
            ->create([
                'telephone' => '+237699998877',
                'pin' => '4242',
            ]);

        $response = $this->post('/login', [
            'telephone' => '+237699998877',
            'pin' => '0000',
            'device_id' => 'test-device-xyz',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();

        $this->assertDatabaseHas('journal_connexions', [
            'telephone' => '+237699998877',
            'resultat' => JournalConnexion::RESULTAT_ECHEC,
        ]);

        $this->assertDatabaseMissing('appareils_memorises', [
            'device_id' => 'test-device-xyz',
        ]);
    }

    public function test_failed_login_with_unknown_phone_still_logs_a_failure(): void
    {
        $response = $this->post('/login', [
            'telephone' => '+237600000000',
            'pin' => '1234',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();

        $this->assertDatabaseHas('journal_connexions', [
            'user_id' => null,
            'telephone' => '+237600000000',
            'resultat' => JournalConnexion::RESULTAT_ECHEC,
        ]);
    }
}
