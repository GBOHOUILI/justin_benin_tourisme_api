<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CircuitIaTest extends TestCase
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

    private function fakeAiResponse(array $body): void
    {
        Http::fake([
            'fake-ai.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($body)]],
                ],
            ], 200),
        ]);
    }

    public function test_returns_503_when_ai_not_configured(): void
    {
        config(['services.ai.base_url' => null, 'services.ai.key' => null, 'services.ai.model' => null]);

        $response = $this->postJson('/api/circuits/generer-ia', ['jours' => 3]);

        $response->assertStatus(503);
    }

    public function test_jours_is_required(): void
    {
        $this->configureAi();

        $response = $this->postJson('/api/circuits/generer-ia', []);

        $response->assertStatus(422)->assertJsonValidationErrors('jours');
    }

    public function test_generates_proposal_from_valid_ai_response(): void
    {
        $this->configureAi();
        $site = Site::factory()->create(['status' => 'valide']);

        $this->fakeAiResponse([
            'titre' => 'Circuit test',
            'budget_estime' => 15000,
            'etapes' => [
                ['type' => 'site', 'id' => $site->id, 'raison' => 'Correspond à vos intérêts'],
            ],
        ]);

        $response = $this->postJson('/api/circuits/generer-ia', [
            'jours' => 2,
            'budget' => 20000,
            'saison' => 'sec',
            'interets' => ['Culture'],
        ]);

        $response->assertOk()
            ->assertJsonPath('titre', 'Circuit test')
            ->assertJsonPath('etapes.0.id', $site->id)
            ->assertJsonPath('etapes.0.type', 'site')
            ->assertJsonPath('etapes.0.item.id', $site->id);
    }

    public function test_discards_hallucinated_ids_not_in_candidate_list(): void
    {
        $this->configureAi();
        $site = Site::factory()->create(['status' => 'valide']);

        $this->fakeAiResponse([
            'titre' => 'Circuit test',
            'etapes' => [
                ['type' => 'site', 'id' => $site->id, 'raison' => 'Réel'],
                ['type' => 'site', 'id' => $site->id + 999, 'raison' => 'Inventé par le modèle'],
            ],
        ]);

        $response = $this->postJson('/api/circuits/generer-ia', ['jours' => 2]);

        $response->assertOk()->assertJsonCount(1, 'etapes');
    }

    public function test_returns_503_on_unparseable_ai_response(): void
    {
        $this->configureAi();
        Site::factory()->create(['status' => 'valide']);

        Http::fake([
            'fake-ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'ceci n\'est pas du JSON']]],
            ], 200),
        ]);

        $response = $this->postJson('/api/circuits/generer-ia', ['jours' => 2]);

        $response->assertStatus(503);
    }
}
