<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Un seul Mailable générique (titre/message/lien) réutilisé pour toutes les
 * notifications transactionnelles (soumission prestataire, confirmation
 * commande, validation responsable...) plutôt qu'une classe par type
 * d'événement - ce serait la même structure recopiée à chaque fois.
 */
class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $titreNotif,
        public string $messageNotif,
        public ?string $lien = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->titreNotif);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'lienComplet' => $this->lien
                    ? rtrim(config('services.frontend.url'), '/') . $this->lien
                    : null,
            ],
        );
    }
}
