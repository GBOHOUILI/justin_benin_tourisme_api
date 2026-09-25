<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Admin;
use App\Models\Commande;
use App\Models\Notification;
use App\Models\Paiement;
use App\Models\Plan;
use App\Models\Prestataire;
use App\Models\Region;
use App\Models\Reservation;
use App\Models\ResponsableRegional;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Couvre le contrôleur générique (les 4 guards partagent le même code, cf.
 * NotificationController::resoudreDestinataire) + les 3 déclencheurs métier
 * demandés (soumission prestataire, validation responsable, confirmation
 * commande). Un seul déclencheur testé de bout en bout par catégorie
 * (Site pour soumission/validation) plutôt que les 5 entités - la logique
 * est copiée à l'identique dans les 5 contrôleurs, déjà éprouvée ailleurs
 * dans ce projet avec ce même niveau de couverture (cf. DemanderPrecisionsTest).
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function prestataireAvecAbonnementActif(): Prestataire
    {
        $prestataire = Prestataire::create([
            'nom_entreprise' => 'Test SARL',
            'email' => 'presta-' . uniqid() . '@test.local',
            'tel' => '01' . rand(10000000, 99999999),
            'password' => 'password',
            'type_prestataire' => 'site',
            'status' => 1,
        ]);

        $plan = Plan::create(['nom' => 'Plan Test', 'prix_mensuel' => 5000, 'nombre_fiches_max' => null]);
        Abonnement::create([
            'id_prestataire' => $prestataire->id,
            'id_plan' => $plan->id,
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addMonth(),
            'statut' => 'actif',
        ]);

        return $prestataire;
    }

    // ── Contrôleur générique ────────────────────────────────────────────

    public function test_index_lists_only_my_own_notifications(): void
    {
        $user = User::factory()->create();
        $autre = User::factory()->create();
        Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $user->id, 'type_evenement' => 'test', 'titre' => 'Pour moi', 'message' => 'x']);
        Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $autre->id, 'type_evenement' => 'test', 'titre' => 'Pas pour moi', 'message' => 'x']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/mes-notifications');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.titre', 'Pour moi');
    }

    public function test_non_lues_counts_unread_only(): void
    {
        $user = User::factory()->create();
        Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $user->id, 'type_evenement' => 'test', 'titre' => 'A', 'message' => 'x', 'lu' => false]);
        Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $user->id, 'type_evenement' => 'test', 'titre' => 'B', 'message' => 'x', 'lu' => true]);

        $this->actingAs($user, 'sanctum')->getJson('/api/mes-notifications/non-lues')
            ->assertOk()->assertJsonPath('nombre', 1);
    }

    public function test_marquer_lu_rejects_someone_elses_notification(): void
    {
        $user = User::factory()->create();
        $autre = User::factory()->create();
        $notif = Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $autre->id, 'type_evenement' => 'test', 'titre' => 'A', 'message' => 'x']);

        $this->actingAs($user, 'sanctum')->patchJson("/api/mes-notifications/{$notif->id}/lu")->assertForbidden();
        $this->assertFalse($notif->fresh()->lu);
    }

    public function test_marquer_toutes_lues_marks_all_as_read(): void
    {
        $user = User::factory()->create();
        Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $user->id, 'type_evenement' => 'test', 'titre' => 'A', 'message' => 'x']);
        Notification::create(['type_destinataire' => 'user', 'id_destinataire' => $user->id, 'type_evenement' => 'test', 'titre' => 'B', 'message' => 'x']);

        $this->actingAs($user, 'sanctum')->patchJson('/api/mes-notifications/tout-lire')->assertOk();

        $this->assertSame(0, Notification::where('type_destinataire', 'user')->where('id_destinataire', $user->id)->where('lu', false)->count());
    }

    public function test_works_for_admin_guard_too(): void
    {
        $admin = Admin::factory()->create();
        Notification::create(['type_destinataire' => 'admin', 'id_destinataire' => $admin->id, 'type_evenement' => 'test', 'titre' => 'Pour admin', 'message' => 'x']);

        $this->actingAs($admin, 'admin')->getJson('/api/admin/mes-notifications')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.titre', 'Pour admin');
    }

    // ── Déclencheur : soumission prestataire ────────────────────────────

    public function test_prestataire_submission_notifies_regional_and_global_responsables(): void
    {
        Mail::fake();
        $region = Region::create(['nom' => 'Littoral']);
        $autreRegion = Region::create(['nom' => 'Atacora']);
        $responsableRegional = ResponsableRegional::create(['nom' => 'A', 'prenom' => 'B', 'tel' => '06' . rand(1000000, 9999999), 'email' => 'resp1@test.local', 'password' => 'password', 'status' => 1, 'id_region' => $region->id]);
        $responsableGlobal = ResponsableRegional::create(['nom' => 'C', 'prenom' => 'D', 'tel' => '06' . rand(1000000, 9999999), 'email' => 'resp2@test.local', 'password' => 'password', 'status' => 1, 'id_region' => null]);
        $responsableAutreRegion = ResponsableRegional::create(['nom' => 'E', 'prenom' => 'F', 'tel' => '06' . rand(1000000, 9999999), 'email' => 'resp3@test.local', 'password' => 'password', 'status' => 1, 'id_region' => $autreRegion->id]);

        $prestataire = $this->prestataireAvecAbonnementActif();
        $cat = \App\Models\CatSite::first() ?? \App\Models\CatSite::create(['libelle' => 'Test']);

        $response = $this->actingAs($prestataire, 'prestataire')->postJson('/api/prestataire/sites', [
            'libelle' => 'Site Test Notif',
            'adresse' => 'Adresse',
            'longitude' => 2.5,
            'latitude' => 6.5,
            'id_cat_site' => $cat->id,
            'id_region' => $region->id,
        ]);

        $response->assertCreated();

        // Le responsable de la région ET le responsable global sont notifiés, pas celui d'une autre région.
        $this->assertDatabaseHas('notification', ['type_destinataire' => 'responsable', 'id_destinataire' => $responsableRegional->id, 'type_evenement' => 'soumission_prestataire']);
        $this->assertDatabaseHas('notification', ['type_destinataire' => 'responsable', 'id_destinataire' => $responsableGlobal->id, 'type_evenement' => 'soumission_prestataire']);
        $this->assertDatabaseMissing('notification', ['type_destinataire' => 'responsable', 'id_destinataire' => $responsableAutreRegion->id]);
    }

    // ── Déclencheur : validation responsable ────────────────────────────

    public function test_valider_notifies_prestataire_owner(): void
    {
        Mail::fake();
        $region = Region::create(['nom' => 'Littoral']);
        $prestataire = $this->prestataireAvecAbonnementActif();
        $responsable = ResponsableRegional::create(['nom' => 'A', 'prenom' => 'B', 'tel' => '06' . rand(1000000, 9999999), 'email' => null, 'password' => 'password', 'status' => 1, 'id_region' => $region->id]);
        $site = Site::factory()->create(['status' => 'en_attente', 'id_prestataire' => $prestataire->id, 'id_responsable' => null, 'id_region' => $region->id]);

        $this->actingAs($responsable, 'responsable')->patchJson("/api/responsable/sites/{$site->id}/valider")->assertOk();

        $this->assertDatabaseHas('notification', [
            'type_destinataire' => 'prestataire',
            'id_destinataire' => $prestataire->id,
            'type_evenement' => 'fiche_validee',
        ]);
    }

    // ── Déclencheur : confirmation commande ──────────────────────────────

    public function test_payment_confirmation_notifies_user(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $commande = Commande::create(['reference' => 'CMD-TEST', 'id_user' => $user->id, 'montant_total' => 1000, 'statut' => 'en_attente']);
        $site = Site::factory()->create(['status' => 'valide']);
        $reservation = Reservation::create(['type' => 'site', 'prix' => 1000, 'nombre' => 1, 'total' => 1000, 'id_site' => $site->id, 'id_user' => $user->id, 'id_commande' => $commande->id, 'statut' => 'en_attente_paiement']);
        $paiement = Paiement::create(['id_commande' => $commande->id, 'montant' => 1000, 'moyen' => 'kkiapay', 'statut' => 'en_attente']);

        // Simule directement l'état "transaction confirmée" plutôt que d'appeler
        // l'API Kkiapay réelle (déjà couvert manuellement en conditions réelles
        // ailleurs dans ce projet, cf. ROADMAP Module Commande + Paiement) - ici
        // on vérifie seulement que reconcilierCommande() déclenche la notification.
        $paiement->update(['statut' => 'reussi', 'reference_transaction' => 'TX-TEST', 'paid_at' => now()]);

        $reflection = new \ReflectionClass(\App\Http\Controllers\PaiementController::class);
        $method = $reflection->getMethod('reconcilierCommande');
        $method->setAccessible(true);
        $method->invoke(new \App\Http\Controllers\PaiementController(), $paiement);

        $this->assertDatabaseHas('notification', [
            'type_destinataire' => 'user',
            'id_destinataire' => $user->id,
            'type_evenement' => 'commande_confirmee',
        ]);
        $this->assertSame('payee', $commande->fresh()->statut);
    }
}
