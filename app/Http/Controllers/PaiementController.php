<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class PaiementController extends Controller
{
    #[
        OA\Patch(
            path: "/api/paiements/{id}/verifier",
            tags: ["Paiements"],
            summary: "Confirmer un paiement Kkiapay via son transactionId (appelé côté client après le widget)",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["transaction_id"],
                    properties: [
                        new OA\Property(property: "transaction_id", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Paiement confirmé ou déjà traité"),
                new OA\Response(response: 403, description: "Accès refusé"),
                new OA\Response(response: 422, description: "Transaction non confirmée par Kkiapay"),
            ],
        ),
    ]
    public function verifier(Request $request, Paiement $paiement)
    {
        if ($paiement->commande->id_user !== $request->user()->id) {
            return response()->json(["message" => "Accès refusé."], 403);
        }

        if ($paiement->statut === "reussi") {
            return response()->json(["message" => "Paiement déjà confirmé.", "paiement" => $paiement]);
        }

        $validated = $request->validate([
            "transaction_id" => "required|string",
        ]);

        $data = $this->verifierAupresKkiapay($validated["transaction_id"]);

        if ($this->transactionReussie($data, $paiement)) {
            $this->marquerReussi($paiement, $validated["transaction_id"], $data);
            return response()->json([
                "message" => "Paiement confirmé.",
                "paiement" => $paiement->fresh(),
            ]);
        }

        $this->marquerEchoue($paiement, $validated["transaction_id"], $data);

        return response()->json([
            "message" => "Le paiement n'a pas pu être confirmé auprès de Kkiapay.",
            "detail" => $data,
        ], 422);
    }

    #[
        OA\Post(
            path: "/api/webhooks/kkiapay",
            tags: ["Paiements"],
            summary: "Webhook de confirmation Kkiapay (appelé par Kkiapay, vérifié via le header x-kkiapay-secret)",
            responses: [
                new OA\Response(response: 200, description: "Événement traité (ou ignoré si non exploitable)"),
                new OA\Response(response: 401, description: "Signature (secret) invalide"),
            ],
        ),
    ]
    public function webhook(Request $request)
    {
        $secretRecu = (string) $request->header("x-kkiapay-secret", "");

        if (!hash_equals((string) config("services.kkiapay.secret"), $secretRecu)) {
            Log::warning("Webhook Kkiapay rejeté : secret invalide.");
            return response()->json(["message" => "Signature invalide."], 401);
        }

        $payload = $request->all();
        $transactionId = $payload["transactionId"] ?? null;

        if (!$transactionId) {
            return response()->json(["message" => "transactionId manquant."], 422);
        }

        $paiement = $this->resoudrePaiement($transactionId, $payload);

        if (!$paiement) {
            Log::warning("Webhook Kkiapay : aucun paiement local ne correspond à cette transaction.", [
                "transactionId" => $transactionId,
            ]);
            // 200 pour éviter les 5 tentatives de relance de Kkiapay sur un événement
            // qu'on ne pourra de toute façon jamais rattacher.
            return response()->json(["message" => "Paiement introuvable, ignoré."], 200);
        }

        if ($paiement->statut === "reussi") {
            return response()->json(["message" => "Déjà traité."]);
        }

        // On ne fait jamais confiance au seul payload du webhook pour le statut/montant :
        // on revérifie auprès de l'API Kkiapay avec nos clés privées avant d'acter quoi que ce soit.
        $data = $this->verifierAupresKkiapay($transactionId);

        if ($this->transactionReussie($data, $paiement)) {
            $this->marquerReussi($paiement, $transactionId, $data);
        } else {
            $this->marquerEchoue($paiement, $transactionId, $data);
        }

        return response()->json(["message" => "ok"]);
    }

    private function verifierAupresKkiapay(string $transactionId): array
    {
        $base = config("services.kkiapay.sandbox")
            ? "https://api-sandbox.kkiapay.me"
            : "https://api.kkiapay.me";

        $response = Http::withHeaders([
            "Accept" => "application/json",
            "X-API-KEY" => config("services.kkiapay.public_key"),
            "X-PRIVATE-KEY" => config("services.kkiapay.private_key"),
            "X-SECRET-KEY" => config("services.kkiapay.secret"),
        ])->post("{$base}/api/v1/transactions/status", [
            "transactionId" => $transactionId,
        ]);

        return $response->json() ?? ["_http_status" => $response->status()];
    }

    private function transactionReussie(array $data, Paiement $paiement): bool
    {
        $status = $data["status"] ?? null;
        $montant = isset($data["amount"]) ? (float) $data["amount"] : null;

        return $status === "SUCCESS"
            && $montant !== null
            && abs($montant - (float) $paiement->montant) < 0.01;
    }

    private function marquerReussi(Paiement $paiement, string $transactionId, array $data): void
    {
        $paiement->update([
            "statut" => "reussi",
            "reference_transaction" => $transactionId,
            "paid_at" => now(),
            "payload_webhook" => $data,
        ]);

        $this->reconcilierCommande($paiement);
    }

    private function marquerEchoue(Paiement $paiement, string $transactionId, array $data): void
    {
        $paiement->update([
            "statut" => "echoue",
            "reference_transaction" => $transactionId,
            "payload_webhook" => $data,
        ]);

        $commande = $paiement->commande;
        $commande->update(["statut" => "echouee"]);

        // On détache les réservations de la commande échouée pour qu'elles redeviennent
        // éligibles à une nouvelle commande (le bouton "Payer" du client refonctionne
        // immédiatement, sans endpoint de retry dédié). La commande/paiement échoués
        // restent en base tels quels, comme trace de la tentative ratée.
        Reservation::where("id_commande", $commande->id)->update(["id_commande" => null]);
    }

    private function reconcilierCommande(Paiement $paiement): void
    {
        $commande = $paiement->commande()->with("paiements")->first();

        $tousReussis = $commande->paiements->isNotEmpty()
            && $commande->paiements->every(fn ($p) => $p->statut === "reussi");

        if (!$tousReussis) {
            return;
        }

        $commande->update(["statut" => "payee"]);

        $user = $commande->user ?? \App\Models\User::find($commande->id_user);
        if ($user) {
            Notification::envoyer(
                "user",
                $user,
                "commande_confirmee",
                "Commande confirmée",
                "Votre paiement a été confirmé, votre commande #{$commande->id} est validée.",
                "/mes-reservations",
            );
        }

        $reservations = Reservation::where("id_commande", $commande->id)->get();
        foreach ($reservations as $reservation) {
            $reservation->update(["statut" => "confirmee"]);

            // Ticket différé jusqu'ici pour une réservation payante (cf. ReservationController::store).
            // Idempotent : si les tickets existent déjà (double webhook + verifier client), on ne les recrée pas.
            if ($reservation->tickets()->count() === 0) {
                for ($i = 0; $i < $reservation->nombre; $i++) {
                    Ticket::create([
                        "numero" => "TCK-" . strtoupper(Str::random(8)),
                        "id_reservation" => $reservation->id,
                    ]);
                }
            }
        }
    }

    /**
     * Retrouve le Paiement local correspondant à une transaction Kkiapay.
     * D'abord par reference_transaction déjà posée (cas où /verifier est passé avant
     * le webhook), sinon via l'identifiant de paiement transmis dans l'attribut `data`
     * du widget et renvoyé dans `stateData` par Kkiapay.
     */
    private function resoudrePaiement(string $transactionId, array $payload): ?Paiement
    {
        $paiement = Paiement::where("reference_transaction", $transactionId)->first();
        if ($paiement) {
            return $paiement;
        }

        $paiementId = $payload["stateData"]["paiement_id"]
            ?? $payload["stateData"]["data"]
            ?? $payload["data"]
            ?? null;

        return $paiementId ? Paiement::find($paiementId) : null;
    }
}
