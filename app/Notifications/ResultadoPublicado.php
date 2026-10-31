<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResultadoPublicado extends Notification
{
    use Queueable;

    public function __construct(
        public int $eventId,
        public string $eventName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->eventId,
            'title' => 'Nuevo resultado publicado',
            'message' => 'Ya puedes consultar los resultados de ' . $this->eventName . '.',
            'url' => route('eventos.resultados', $this->eventId),
        ];
    }
}