<?php

namespace App\Models;

use App\Mail\NotificationEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notification extends Model
{
    protected $table = 'notification';

    protected $fillable = [
        'type_destinataire',
        'id_destinataire',
        'type_evenement',
        'titre',
        'message',
        'lien',
        'lu',
    ];

    protected $casts = [
        'lu' => 'boolean',
    ];

    /**
     * Crée la notification in-app et tente l'envoi par email si le compte a
     * un email renseigné (Admin/ResponsableRegional peuvent ne pas en avoir,
     * cf. migration "mot de passe oublié" - dans ce cas, in-app seulement).
     * Un échec d'envoi email ne doit jamais faire échouer l'action déclenchante
     * (soumission de fiche, confirmation de commande...) - toujours en best-effort.
     */
    public static function envoyer(
        string $typeDestinataire,
        Prestataire|User|Admin|ResponsableRegional $destinataire,
        string $typeEvenement,
        string $titre,
        string $message,
        ?string $lien = null,
    ): self {
        $notification = self::create([
            'type_destinataire' => $typeDestinataire,
            'id_destinataire' => $destinataire->id,
            'type_evenement' => $typeEvenement,
            'titre' => $titre,
            'message' => $message,
            'lien' => $lien,
        ]);

        if ($destinataire->email) {
            try {
                Mail::to($destinataire->email)->send(new NotificationEmail($titre, $message, $lien));
            } catch (\Throwable $e) {
                Log::warning('Notification : envoi email échoué', ['exception' => $e->getMessage()]);
            }
        }

        return $notification;
    }

    /**
     * Notifie tous les responsables régionaux qui verraient cette fiche dans
     * leur file "à valider" (globaux + celui de la région concernée) - même
     * périmètre que ResponsableRegionalController::aValider().
     */
    public static function notifierResponsablesRegion(
        ?int $idRegion,
        string $typeEvenement,
        string $titre,
        string $message,
        ?string $lien = null,
    ): void {
        $responsables = ResponsableRegional::where(function ($q) use ($idRegion) {
            $q->whereNull('id_region');
            if ($idRegion) {
                $q->orWhere('id_region', $idRegion);
            }
        })->get();

        foreach ($responsables as $responsable) {
            self::envoyer('responsable', $responsable, $typeEvenement, $titre, $message, $lien);
        }
    }
}
