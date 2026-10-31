<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevaSolicitudInscripcion extends Notification
{
    use Queueable;

    public function __construct(
        public int $registrationId,
        public string $eventName,
        public string $participantName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'registration_id' => $this->registrationId,
            'event_name' => $this->eventName,
            'participant_name' => $this->participantName,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva solicitud de inscripción')
            ->line('El piloto ' . $this->participantName . ' ha enviado una nueva solicitud para el evento ' . $this->eventName . '.')
            ->line('Debe revisar la solicitud y decidir si acepta o rechaza la inscripción.')
            ->action('Ver solicitudes', url('/inscripciones'));
    }
}
