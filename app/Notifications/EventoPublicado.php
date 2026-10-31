<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventoPublicado extends Notification
{
    use Queueable;

    public function __construct(
        public int $eventId,
        public string $eventName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo evento de motocross: ' . $this->eventName)
            ->line('Se ha publicado un nuevo evento de motocross.')
            ->line($this->eventName)
            ->action('Ver evento', route('eventos.show', $this->eventId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->eventId,
            'title' => 'Nuevo evento publicado',
            'message' => 'Ya puedes consultar el evento ' . $this->eventName . '.',
            'url' => route('eventos.show', $this->eventId),
        ];
    }
}