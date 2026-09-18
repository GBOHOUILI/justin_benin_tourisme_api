<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\FactureAbonnement;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AbonnementController extends Controller
{
    #[
        OA\Get(
            path: "/api/prestataire/abonnement",
            tags: ["Abonnements"],
            summary: "Abonnement en cours du prestataire connecté (statut, plan, factures)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Abonnement courant, ou null si aucun")],
        ),
    ]
    public function statut(Request $request)
    {
        $abonnement = $request->user()->abonnementCourant();

        return response()->json([
            'abonnement' => $abonnement?->load(['plan', 'factures']),
            'actif' => $request->user()->abonnementActif(),
        ]);
    }

    #[
        OA\Post(
            path: "/api/prestataire/abonnements",
            tags: ["Abonnements"],
            summary: "Souscrire à un plan (ou générer une nouvelle facture pour un abonnement en attente de paiement)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["id_plan"],
                    properties: [new OA\Property(property: "id_plan", type: "integer")],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Abonnement (nouveau ou existant en attente) avec sa facture à payer"),
                new OA\Response(response: 422, description: "Un abonnement déjà actif couvre encore la période en cours"),
            ],
        ),
    ]
    public function souscrire(Request $request)
    {
        $validated = $request->validate([
            'id_plan' => 'required|exists:plan,id',
        ]);

        $prestataire = $request->user();

        if ($prestataire->abonnementActif()) {
            return response()->json([
                'message' => "Un abonnement actif couvre déjà la période en cours.",
            ], 422);
        }

        $plan = Plan::findOrFail($validated['id_plan']);

        // Un abonnement déjà en attente de paiement est réutilisé (plan mis à jour au
        // passage) plutôt que d'en empiler un nouveau à chaque tentative - une seule
        // nouvelle facture est créée pour la tentative de paiement en cours.
        $abonnement = $prestataire->abonnements()->where('statut', 'en_attente')->latest()->first();

        if ($abonnement) {
            $abonnement->update(['id_plan' => $plan->id]);
        } else {
            $abonnement = Abonnement::create([
                'id_prestataire' => $prestataire->id,
                'id_plan' => $plan->id,
                'statut' => 'en_attente',
            ]);
        }

        $facture = FactureAbonnement::create([
            'id_abonnement' => $abonnement->id,
            'montant' => $plan->prix_mensuel,
            'date_facturation' => now(),
            'statut_paiement' => 'en_attente',
        ]);

        return response()->json(
            $abonnement->load(['plan', 'factures'])->setAttribute('facture_a_payer', $facture),
            201,
        );
    }

    #[
        OA\Patch(
            path: "/api/prestataire/factures-abonnement/{id}/verifier",
            tags: ["Abonnements"],
            summary: "Confirmer le paiement Kkiapay d'une facture d'abonnement via son transactionId",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["transaction_id"],
                    properties: [new OA\Property(property: "transaction_id", type: "string")],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Paiement confirmé (ou déjà traité), abonnement activé"),
                new OA\Response(response: 403, description: "Accès refusé"),
                new OA\Response(response: 422, description: "Transaction non confirmée par Kkiapay"),
            ],
        ),
    ]
    public function verifier(Request $request, FactureAbonnement $factureAbonnement)
    {
        if ($factureAbonnement->abonnement->id_prestataire !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($factureAbonnement->statut_paiement === 'payee') {
            return response()->json([
                'message' => 'Paiement déjà confirmé.',
                'facture' => $factureAbonnement,
            ]);
        }

        $validated = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $data = $this->verifierAupresKkiapay($validated['transaction_id']);

        if ($this->transactionReussie($data, $factureAbonnement)) {
            $this->marquerReussie($factureAbonnement, $validated['transaction_id'], $data);
            return response()->json([
                'message' => 'Paiement confirmé, abonnement activé.',
                'facture' => $factureAbonnement->fresh(),
                'abonnement' => $factureAbonnement->abonnement->fresh(),
            ]);
        }

        $this->marquerEchouee($factureAbonnement, $validated['transaction_id'], $data);

        return response()->json([
            'message' => "Le paiement n'a pas pu être confirmé auprès de Kkiapay.",
            'detail' => $data,
        ], 422);
    }

    #[
        OA\Post(
            path: "/api/webhooks/kkiapay-abonnement",
            tags: ["Abonnements"],
            summary: "Webhook de confirmation Kkiapay pour les factures d'abonnement (vérifié via x-kkiapay-secret)",
            responses: [
                new OA\Response(response: 200, description: "Événement traité (ou ignoré si non exploitable)"),
                new OA\Response(response: 401, description: "Signature (secret) invalide"),
            ],
        ),
    ]
    public function webhook(Request $request)
    {
        $secretRecu = (string) $request->header('x-kkiapay-secret', '');

        if (!hash_equals((string) config('services.kkiapay.secret'), $secretRecu)) {
            Log::warning('Webhook Kkiapay (abonnement) rejeté : secret invalide.');
            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        $payload = $request->all();
        $transactionId = $payload['transactionId'] ?? null;

        if (!$transactionId) {
            return response()->json(['message' => 'transactionId manquant.'], 422);
        }

        $facture = $this->resoudreFacture($transactionId, $payload);

        if (!$facture) {
            Log::warning('Webhook Kkiapay (abonnement) : aucune facture locale ne correspond à cette transaction.', [
                'transactionId' => $transactionId,
            ]);
            return response()->json(['message' => 'Facture introuvable, ignoré.'], 200);
        }

        if ($facture->statut_paiement === 'payee') {
            return response()->json(['message' => 'Déjà traité.']);
        }

        $data = $this->verifierAupresKkiapay($transactionId);

        if ($this->transactionReussie($data, $facture)) {
            $this->marquerReussie($facture, $transactionId, $data);
        } else {
            $this->marquerEchouee($facture, $transactionId, $data);
        }

        return response()->json(['message' => 'ok']);
    }

    #[
        OA\Get(
            path: "/api/admin/abonnements",
            tags: ["Abonnements"],
            summary: "Lister tous les abonnements (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée des abonnements")],
        ),
    ]
    public function adminIndex()
    {
        return response()->json(
            Abonnement::with(['prestataire', 'plan'])->latest()->paginate(20),
        );
    }

    private function verifierAupresKkiapay(string $transactionId): array
    {
        $base = config('services.kkiapay.sandbox')
            ? 'https://api-sandbox.kkiapay.me'
            : 'https://api.kkiapay.me';

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => config('services.kkiapay.public_key'),
            'X-PRIVATE-KEY' => config('services.kkiapay.private_key'),
            'X-SECRET-KEY' => config('services.kkiapay.secret'),
        ])->post("{$base}/api/v1/transactions/status", [
            'transactionId' => $transactionId,
        ]);

        return $response->json() ?? ['_http_status' => $response->status()];
    }

    private function transactionReussie(array $data, FactureAbonnement $facture): bool
    {
        $status = $data['status'] ?? null;
        $montant = isset($data['amount']) ? (float) $data['amount'] : null;

        return $status === 'SUCCESS'
            && $montant !== null
            && abs($montant - (float) $facture->montant) < 0.01;
    }

    private function marquerReussie(FactureAbonnement $facture, string $transactionId, array $data): void
    {
        $facture->update([
            'statut_paiement' => 'payee',
            'reference_transaction' => $transactionId,
            'payload_webhook' => $data,
        ]);

        $abonnement = $facture->abonnement;

        // Un renouvellement repart de la date de fin en cours si elle n'est pas encore
        // passée (pas de mois "perdu"), sinon repart d'aujourd'hui.
        $depart = $abonnement->date_fin && $abonnement->date_fin->copy()->endOfDay()->isFuture()
            ? $abonnement->date_fin->copy()
            : now();

        $abonnement->update([
            'statut' => 'actif',
            'date_debut' => $abonnement->date_debut ?? now(),
            'date_fin' => $depart->copy()->addMonth(),
        ]);
    }

    private function marquerEchouee(FactureAbonnement $facture, string $transactionId, array $data): void
    {
        $facture->update([
            'statut_paiement' => 'echouee',
            'reference_transaction' => $transactionId,
            'payload_webhook' => $data,
        ]);

        // L'abonnement reste en_attente : une nouvelle facture sera créée au prochain
        // appel à souscrire(), sans perdre la trace de cette tentative ratée.
    }

    /**
     * Retrouve la FactureAbonnement locale correspondant à une transaction Kkiapay.
     * D'abord par reference_transaction déjà posée, sinon via l'identifiant transmis
     * dans l'attribut `data` du widget et renvoyé dans `stateData` par Kkiapay.
     */
    private function resoudreFacture(string $transactionId, array $payload): ?FactureAbonnement
    {
        $facture = FactureAbonnement::where('reference_transaction', $transactionId)->first();
        if ($facture) {
            return $facture;
        }

        $factureId = $payload['stateData']['facture_id']
            ?? $payload['stateData']['data']
            ?? $payload['data']
            ?? null;

        return $factureId ? FactureAbonnement::find($factureId) : null;
    }
}
