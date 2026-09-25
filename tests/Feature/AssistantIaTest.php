<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantIaTest extends TestCase
{
    use RefreshDatabase;

    private function configureAi(): void
    {
        config([
            'services.ai.base_url' => 'https://fake-ai.test/v1',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'test-model',
        ]);
    }

    private function fakeAiResponse(string $contenu): void
    {
        Http::fake([
            'fake-ai.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => $contenu]],
                ],
            ], 200),
        ]);
    }

    public function test_returns_503_when_ai_not_configured(): void
    {
        config(['services.ai.base_url' => null, 'services.ai.key' => null, 'services.ai.model' => null]);

        $response = $this->postJson('/api/assistant/chat', [
            'messages' => [['role' => 'user', 'content' => 'Bonjour']],
        ]);

        $response->assertStatus(503);
    }

    public function test_messages_is_required(): void
    {
        $this->configureAi();

        $response = $this->postJson('/api/assistant/chat', []);

        $response->assertStatus(422)->assertJsonValidationErrors('messages');
    }

    public function test_returns_plain_answer_without_circuit_block(): void
    {
        $this->configureAi();
        $this->fakeAiResponse("Le Palais Royal d'Abomey est un site historique incontournable.");

        $response = $this->postJson('/api/assistant/chat', [
            'messages' => [['role' => 'user', 'content' => "Que voir à Abomey ?"]],
        ]);

        $response->assertOk()
            ->assertJsonPath('reponse', "Le Palais Royal d'Abomey est un site historique incontournable.")
            ->assertJsonPath('circuit', null);
    }

    public function test_extracts_and_validates_circuit_block_discarding_hallucinated_ids(): void
    {
        $this->configureAi();
        $site = Site::factory()->create(['status' => 'valide']);

        $contenu = "Voici un circuit adapté.\n<<<CIRCUIT>>>"
            . json_encode([
                'titre' => 'Circuit test',
                'budget_estime' => 15000,
                'etapes' => [
                    ['type' => 'site', 'id' => $site->id, 'raison' => 'Réel'],
                    ['type' => 'site', 'id' => $site->id + 999, 'raison' => 'Inventé par le modèle'],
                ],
            ])
            . '<<<FIN>>>';
        $this->fakeAiResponse($contenu);

        $response = $this->postJson('/api/assistant/chat', [
            'messages' => [['role' => 'user', 'content' => 'Fais-moi un circuit de 2 jours']],
        ]);

        $response->assertOk()
            ->assertJsonPath('reponse', 'Voici un circuit adapté.')
            ->assertJsonPath('circuit.titre', 'Circuit test')
            ->assertJsonCount(1, 'circuit.etapes')
            ->assertJsonPath('circuit.etapes.0.id', $site->id);
    }

    public function test_returns_503_on_empty_model_response(): void
    {
        $this->configureAi();

        Http::fake([
            'fake-ai.test/*' => Http::response(['choices' => [['message' => ['content' => '']]]], 200),
        ]);

        $response = $this->postJson('/api/assistant/chat', [
            'messages' => [['role' => 'user', 'content' => 'Bonjour']],
        ]);

        $response->assertStatus(503);
    }
}
