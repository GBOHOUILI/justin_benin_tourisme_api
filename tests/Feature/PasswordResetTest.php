<?php

namespace Tests\Feature;

use App\Mail\ReinitialisationMotDePasse;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * "Mot de passe oublié", commun aux 4 types de comptes (PasswordResetController).
 * Testé principalement sur User (le type le plus courant) + un smoke test sur
 * Admin pour vérifier que le dispatch par `type` généralise correctement -
 * même logique de couverture que DemanderPrecisionsTest (Site + smoke Hotel).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_demander_returns_generic_message_even_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mot-de-passe/oublie', [
            'type' => 'user',
            'email' => 'inconnu@test.local',
        ]);

        $response->assertOk()->assertJsonPath('message', "Si un compte existe avec cet email, un lien de réinitialisation vient de lui être envoyé.");
        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset', 0);
    }

    public function test_demander_creates_token_and_sends_email_for_existing_user(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'presente@test.local']);

        $response = $this->postJson('/api/mot-de-passe/oublie', [
            'type' => 'user',
            'email' => 'presente@test.local',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('password_reset', ['type' => 'user', 'email' => 'presente@test.local']);
        Mail::assertSent(ReinitialisationMotDePasse::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && str_contains($mail->lienReinitialisation, '/mot-de-passe/reinitialiser/user');
        });
    }

    public function test_reinitialiser_updates_password_and_revokes_existing_tokens(): void
    {
        $user = User::factory()->create();
        $ancienToken = $user->createToken('test')->plainTextToken;
        $tokenClair = Str::random(64);

        DB::table('password_reset')->insert([
            'type' => 'user',
            'email' => $user->email,
            'token' => Hash::make($tokenClair),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/mot-de-passe/reinitialiser', [
            'type' => 'user',
            'email' => $user->email,
            'token' => $tokenClair,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset', 0);
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_reinitialiser_rejects_invalid_token(): void
    {
        $user = User::factory()->create();
        DB::table('password_reset')->insert([
            'type' => 'user',
            'email' => $user->email,
            'token' => Hash::make('le-bon-token'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/mot-de-passe/reinitialiser', [
            'type' => 'user',
            'email' => $user->email,
            'token' => 'un-mauvais-token',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertStatus(422);
        $this->assertFalse(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
    }

    public function test_reinitialiser_rejects_expired_token(): void
    {
        $user = User::factory()->create();
        $tokenClair = Str::random(64);
        DB::table('password_reset')->insert([
            'type' => 'user',
            'email' => $user->email,
            'token' => Hash::make($tokenClair),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this->postJson('/api/mot-de-passe/reinitialiser', [
            'type' => 'user',
            'email' => $user->email,
            'token' => $tokenClair,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertStatus(422);
    }

    public function test_works_for_admin_type_too(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create(['email' => 'admin-qa@test.local']);

        $this->postJson('/api/mot-de-passe/oublie', ['type' => 'admin', 'email' => $admin->email])->assertOk();
        $this->assertDatabaseHas('password_reset', ['type' => 'admin', 'email' => $admin->email]);

        $tokenClair = Str::random(64);
        DB::table('password_reset')->where('type', 'admin')->where('email', $admin->email)->update([
            'token' => Hash::make($tokenClair),
        ]);

        $response = $this->postJson('/api/mot-de-passe/reinitialiser', [
            'type' => 'admin',
            'email' => $admin->email,
            'token' => $tokenClair,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $admin->fresh()->password));
    }
}
