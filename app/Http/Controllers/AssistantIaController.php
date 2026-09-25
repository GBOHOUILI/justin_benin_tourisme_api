<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Models\Site;
use App\Models\Transport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AssistantIaController extends Controller
{
    // Contrairement à CircuitIaController (contraintes structurées ->
    // proposition), ici le visiteur pose une question libre et reçoit une
    // vraie réponse conversationnelle. Public comme le reste du module
    // circuit - discuter avec l'assistant ne nécessite pas de compte.
    // Le modèle peut, en plus de sa réponse texte, proposer un circuit :
    // celui-ci suit exactement le même contrat et la même règle anti-
    // hallucination (id revalidés contre le catalogue réel) que
    // CircuitIaController::generer.
    #[
        OA\Post(
            path: "/api/assistant/chat",
            tags: ["Circuits"],
            summary: "Discuter librement avec l'assistant IA (questions sur sites, événements, hôtels, restaurants, transports, ou demande de circuit)",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["messages"],
                    properties: [
                        new OA\Property(
                            property: "messages",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "role", type: "string", enum: ["user", "assistant"]),
                                    new OA\Property(property: "content", type: "string"),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "{ reponse, circuit: {titre, budget_estime, etapes} | null }"),
                new OA\Response(response: 422, description: "Messages invalides"),
                new OA\Response(response: 503, description: "IA non configurée ou indisponible"),
            ],
        ),
    ]
    public function discuter(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1|max:20',
            'messages.*.role' => 'required|string|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
        ]);

        $baseUrl = config('services.ai.base_url');
        $apiKey = config('services.ai.key');
        $model = config('services.ai.model');

        if (! $baseUrl || ! $apiKey || ! $model) {
            return response()->json([
                'message' => "L'assistant n'est pas encore configuré (clé API manquante).",
            ], 503);
        }

        $candidats = $this->candidats();

        $messages = [['role' => 'system', 'content' => $this->construireSystemPrompt($candidats)]];
        foreach ($validated['messages'] as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(25)
                ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.5,
                    'messages' => $messages,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Assistant IA : appel au modèle échoué', ['exception' => $e->getMessage()]);
            return response()->json(['message' => "L'assistant est momentanément indisponible."], 503);
        }

        if ($response->failed()) {
            Log::warning('Assistant IA : réponse en erreur', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['message' => "L'assistant est momentanément indisponible."], 503);
        }

        $contenu = $response->json('choices.0.message.content');

        if (! $contenu) {
            Log::warning('Assistant IA : réponse vide');
            return response()->json(['message' => "Réponse du modèle illisible, réessayez."], 503);
        }

        [$reponse, $circuitBrut] = $this->separerCircuit($contenu);

        if (trim($reponse) === '') {
            return response()->json(['message' => "Réponse du modèle illisible, réessayez."], 503);
        }

        return response()->json([
            'reponse' => trim($reponse),
            'circuit' => $circuitBrut ? $this->validerCircuit($circuitBrut, $candidats) : null,
        ]);
    }

    /**
     * Catalogue réel (sites, événements, hôtels, restaurants, transports
     * validés), format compact pour le prompt et gabarit de vérité pour
     * valider toute proposition de circuit renvoyée par le modèle.
     */
    private function candidats()
    {
        $sites = Site::with(['categorie', 'prix'])
            ->where('status', 'valide')
            ->limit(25)
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
                    str($s->description ?? '')->limit(140),
                    $s->prix->first()?->montant ?? 'gratuit',
                ),
            ]);

        $evenements = Evenement::with(['categorie', 'prix'])
            ->where('status', 'valide')
            ->limit(25)
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
                    str($e->description ?? '')->limit(140),
                    $e->prix->first()?->montant ?? 'gratuit',
                ),
            ]);

        $hotels = Hotel::with('chambres')
            ->where('status', 'valide')
            ->limit(20)
            ->get()
            ->map(function (Hotel $h) {
                $prix = $h->chambres->pluck('prix_nuit')->filter();
                $gamme = $prix->isEmpty() ? 'tarifs non précisés' : sprintf('de %s à %s FCFA/nuit', $prix->min(), $prix->max());
                return [
                    'type' => 'hotel',
                    'id' => $h->id,
                    'item' => $h,
                    'resume' => sprintf(
                        '#hotel:%d — %s (%s) — %s étoile(s) — %s — %s',
                        $h->id,
                        $h->libelle,
                        $h->adresse,
                        $h->nombre_etoiles ?? '?',
                        $gamme,
                        str($h->description ?? '')->limit(100),
                    ),
                ];
            });

        $restaurants = Restaurant::with('plats')
            ->where('status', 'valide')
            ->limit(20)
            ->get()
            ->map(function (Restaurant $r) {
                $prix = $r->plats->pluck('prix')->filter();
                $gamme = $prix->isEmpty() ? 'tarifs non précisés' : sprintf('plats de %s à %s FCFA', $prix->min(), $prix->max());
                return [
                    'type' => 'restaurant',
                    'id' => $r->id,
                    'item' => $r,
                    'resume' => sprintf(
                        '#restaurant:%d — %s (%s) — cuisine %s — %s',
                        $r->id,
                        $r->libelle,
                        $r->adresse,
                        $r->type_cuisine ?? 'non précisée',
                        $gamme,
                    ),
                ];
            });

        $transports = Transport::with('trajets')
            ->where('status', 'valide')
            ->limit(20)
            ->get()
            ->map(function (Transport $t) {
                $prix = $t->trajets->pluck('prix')->filter();
                $gamme = $prix->isEmpty() ? 'tarifs non précisés' : sprintf('trajets de %s à %s FCFA', $prix->min(), $prix->max());
                return [
                    'type' => 'transport',
                    'id' => $t->id,
                    'item' => $t,
                    'resume' => sprintf(
                        '#transport:%d — %s (%s) — %s — %s',
                        $t->id,
                        $t->libelle,
                        $t->adresse,
                        $t->type_transport ?? 'transport',
                        $gamme,
                    ),
                ];
            });

        return $sites->concat($evenements)->concat($hotels)->concat($restaurants)->concat($transports);
    }

    private function construireSystemPrompt($candidats): string
    {
        $liste = $candidats->pluck('resume')->implode("\n");

        return <<<TXT
        Tu es l'assistant IA de Totché, une plateforme de tourisme au Bénin. Tu réponds en français, de façon concise, naturelle et amicale, aux questions des visiteurs sur les sites touristiques, événements, hôtels, restaurants et transports réellement disponibles ci-dessous (chacun préfixé par son identifiant exact "#type:id").

        Règles strictes :
        - N'invente jamais un prix, un horaire ou une information qui ne figure pas dans le catalogue ci-dessous ; si tu ne sais pas, dis-le clairement plutôt que de deviner.
        - Si la question ne concerne pas le tourisme au Bénin ou la plateforme Totché, réponds brièvement que tu es spécialisé sur ce sujet.
        - Si l'utilisateur souhaite un circuit ou un itinéraire (explicitement, ou en te laissant choisir), termine ta réponse texte normale puis ajoute, sur une nouvelle ligne, un bloc EXACTEMENT au format suivant (rien d'autre après) :
        <<<CIRCUIT>>>{"titre": "titre court", "budget_estime": nombre_ou_null, "etapes": [{"type": "site|evenement|hotel|restaurant|transport", "id": nombre_entier, "raison": "phrase courte"}]}<<<FIN>>>
          Les "id" doivent être copiés exactement depuis les identifiants "#type:id" ci-dessous, jamais inventés. N'ajoute ce bloc que si un circuit a réellement été demandé.

        Catalogue disponible :
        {$liste}
        TXT;
    }

    /**
     * Sépare le texte à afficher du bloc circuit optionnel. Le JSON n'est
     * jamais recopié tel quel dans la réponse HTTP à ce stade - il est
     * revalidé par validerCircuit() avant d'être renvoyé au client.
     */
    private function separerCircuit(string $contenu): array
    {
        if (preg_match('/<<<CIRCUIT>>>(.*?)<<<FIN>>>/s', $contenu, $m)) {
            $reponse = trim(str_replace($m[0], '', $contenu));
            $decode = json_decode(trim($m[1]), true);
            return [$reponse, is_array($decode) ? $decode : null];
        }

        return [$contenu, null];
    }

    /** Jamais confiance aveugle dans les id renvoyés par le modèle - ne garder que ceux qui existent réellement dans le catalogue envoyé. */
    private function validerCircuit(array $circuitBrut, $candidats): ?array
    {
        $parId = $candidats->keyBy(fn ($c) => $c['type'] . ':' . $c['id']);
        $etapes = collect($circuitBrut['etapes'] ?? [])
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
            return null;
        }

        return [
            'titre' => is_string($circuitBrut['titre'] ?? null) ? $circuitBrut['titre'] : 'Circuit proposé',
            'budget_estime' => is_numeric($circuitBrut['budget_estime'] ?? null) ? (float) $circuitBrut['budget_estime'] : null,
            'etapes' => $etapes,
        ];
    }
}
