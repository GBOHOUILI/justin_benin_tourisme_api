<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class CircuitIaController extends Controller
{
    // Le module Circuit est public (cf. ROADMAP : composer un circuit ne
    // nécessite pas de compte, seul l'enregistrement en nécessite un) - cette
    // génération l'est donc aussi. Le résultat est une PROPOSITION, jamais
    // persistée ici : le touriste la retrouve pré-remplie dans le brouillon
    // existant (Circuits.jsx), la modifie et l'enregistre via le flux normal
    // (POST /circuits + POST /circuits/{id}/etapes, déjà en place).
    #[
        OA\Post(
            path: "/api/circuits/generer-ia",
            tags: ["Circuits"],
            summary: "Proposer un circuit via IA à partir de contraintes (budget, jours, saison, intérêts)",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["jours"],
                    properties: [
                        new OA\Property(property: "budget", type: "number", description: "Budget total en FCFA"),
                        new OA\Property(property: "jours", type: "integer", minimum: 1, maximum: 14),
                        new OA\Property(property: "saison", type: "string", enum: ["sec", "pluie"]),
                        new OA\Property(property: "interets", type: "array", items: new OA\Items(type: "string")),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "{ titre, budget_estime, etapes: [{type, id, raison, item}] }"),
                new OA\Response(response: 422, description: "Contraintes invalides"),
                new OA\Response(response: 503, description: "IA non configurée ou indisponible"),
            ],
        ),
    ]
    public function generer(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'nullable|numeric|min:0',
            'jours' => 'required|integer|min:1|max:14',
            'saison' => 'nullable|string|in:sec,pluie',
            'interets' => 'nullable|array',
            'interets.*' => 'string|max:50',
        ]);

        $baseUrl = config('services.ai.base_url');
        $apiKey = config('services.ai.key');
        $model = config('services.ai.model');

        if (! $baseUrl || ! $apiKey || ! $model) {
            return response()->json([
                'message' => "Le générateur de circuit par IA n'est pas encore configuré (clé API manquante).",
            ], 503);
        }

        $candidats = $this->candidats();

        if ($candidats->isEmpty()) {
            return response()->json(['message' => 'Aucun site ou événement disponible pour composer un circuit.'], 422);
        }

        $prompt = $this->construirePrompt($candidats, $validated);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(25)
                ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.6,
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt['system']],
                        ['role' => 'user', 'content' => $prompt['user']],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Circuit IA : appel au modèle échoué', ['exception' => $e->getMessage()]);
            return response()->json(['message' => "Le générateur de circuit est momentanément indisponible."], 503);
        }

        if ($response->failed()) {
            Log::warning('Circuit IA : réponse en erreur', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['message' => "Le générateur de circuit est momentanément indisponible."], 503);
        }

        $contenu = $response->json('choices.0.message.content');
        $propose = $this->parserReponse($contenu);

        if (! $propose) {
            Log::warning('Circuit IA : réponse illisible', ['contenu' => $contenu]);
            return response()->json(['message' => "Réponse du modèle illisible, réessayez."], 503);
        }

        // Jamais confiance aveugle dans les id renvoyés par le modèle - ne
        // garder que ceux qui existent réellement dans la liste envoyée.
        $parId = $candidats->keyBy(fn ($c) => $c['type'] . ':' . $c['id']);
        $etapes = collect($propose['etapes'] ?? [])
            ->filter(fn ($e) => isset($e['type'], $e['id']) && $parId->has($e['type'] . ':' . $e['id']))
            ->take(10)
            ->map(function ($e) use ($parId) {
                $candidat = $parId->get($e['type'] . ':' . $e['id']);
                return [
                    'type' => $e['type'],
                    'id' => $e['id'],
                    'raison' => is_string($e['raison'] ?? null) ? $e['raison'] : null,
                    'item' => $candidat['item'],
                ];
            })
            ->values();

        if ($etapes->isEmpty()) {
            return response()->json(['message' => "Le modèle n'a proposé aucune étape valide, réessayez."], 503);
        }

        return response()->json([
            'titre' => is_string($propose['titre'] ?? null) ? $propose['titre'] : 'Circuit proposé',
            'budget_estime' => is_numeric($propose['budget_estime'] ?? null) ? (float) $propose['budget_estime'] : null,
            'etapes' => $etapes,
        ]);
    }

    /** Sites + événements validés, format compact pour le prompt (et gabarit de vérité pour valider la réponse du modèle). */
    private function candidats()
    {
        $sites = Site::with(['categorie', 'region', 'prix'])
            ->where('status', 'valide')
            ->limit(30)
            ->get()
            ->map(fn (Site $s) => [
                'type' => 'site',
                'id' => $s->id,
                'item' => $s,
                'resume' => sprintf(
                    '#site:%d — %s (%s%s) — %s — prix indicatif: %s FCFA',
                    $s->id,
                    $s->libelle,
                    $s->adresse,
                    $s->categorie ? ", {$s->categorie->libelle}" : '',
                    str($s->description ?? '')->limit(120),
                    $s->prix->first()?->montant ?? 'gratuit',
                ),
            ]);

        $evenements = Evenement::with(['categorie', 'region', 'prix'])
            ->where('status', 'valide')
            ->limit(30)
            ->get()
            ->map(fn (Evenement $e) => [
                'type' => 'evenement',
                'id' => $e->id,
                'item' => $e,
                'resume' => sprintf(
                    '#evenement:%d — %s (%s%s) — %s — prix indicatif: %s FCFA',
                    $e->id,
                    $e->libelle,
                    $e->adresse,
                    $e->categorie ? ", {$e->categorie->libelle}" : '',
                    str($e->description ?? '')->limit(120),
                    $e->prix->first()?->montant ?? 'gratuit',
                ),
            ]);

        return $sites->concat($evenements);
    }

    private function construirePrompt($candidats, array $contraintes): array
    {
        $liste = $candidats->pluck('resume')->implode("\n");
        $saisonLabel = ($contraintes['saison'] ?? null) === 'pluie' ? 'saison des pluies' : 'saison sèche';
        $interets = ! empty($contraintes['interets']) ? implode(', ', $contraintes['interets']) : 'non précisés';
        $budget = $contraintes['budget'] ?? null;

        $system = <<<TXT
        Tu composes des circuits touristiques au Bénin pour la plateforme Totché. Tu reçois une liste de sites et événements réellement disponibles (chacun préfixé par son identifiant exact "#type:id") et les contraintes d'un voyageur. Choisis et ordonne un sous-ensemble cohérent géographiquement et thématiquement (entre 3 et 8 étapes selon le nombre de jours, jamais plus que le nombre de candidats disponibles), qui respecte au mieux le budget total et les centres d'intérêt donnés.
        Réponds UNIQUEMENT avec un objet JSON valide, sans texte avant/après, sans balises markdown, au format exact :
        {"titre": "titre court du circuit", "budget_estime": nombre_ou_null, "etapes": [{"type": "site ou evenement", "id": nombre_entier, "raison": "une phrase courte expliquant ce choix"}]}
        Les valeurs "id" doivent être copiées exactement depuis les identifiants "#type:id" fournis, jamais inventées.
        TXT;

        $user = <<<TXT
        Contraintes du voyageur :
        - Durée du séjour : {$contraintes['jours']} jour(s)
        - Budget total : {$this->formatBudget($budget)}
        - Période : {$saisonLabel}
        - Centres d'intérêt : {$interets}

        Sites et événements disponibles :
        {$liste}
        TXT;

        return ['system' => $system, 'user' => $user];
    }

    private function formatBudget($budget): string
    {
        return $budget ? number_format((float) $budget, 0, ',', ' ') . ' FCFA' : 'non précisé';
    }

    private function parserReponse(?string $contenu): ?array
    {
        if (! $contenu) {
            return null;
        }

        // Certains modèles enveloppent quand même la réponse dans des
        // balises ```json ... ``` malgré la consigne - on les retire avant
        // de parser, sans jamais faire confiance au contenu au-delà de ça.
        $nettoye = trim(preg_replace('/^```(json)?|```$/m', '', trim($contenu)));
        $decode = json_decode($nettoye, true);

        return is_array($decode) ? $decode : null;
    }
}
