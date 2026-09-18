<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventPublished extends Notification
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
            'title' => 'Nuevo evento publicado',
            'message' => 'Ya puedes consultar el evento ' . $this->eventName . '.',
            'url' => route('eventos.show', $this->eventId),
        ];
    }
}