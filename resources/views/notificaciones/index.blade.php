<x-app-layout>
    <div class="pilot-notifications-screen">
        <header class="pilot-notifications-topbar">
            <a href="{{ route('dashboard') }}" aria-label="Volver al panel">‹</a>
            <h1>Notificaciones</h1>
            <a class="pilot-notifications-avatar" href="{{ route('profile.edit') }}" aria-label="Mi perfil">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</a>
        </header>

        <section class="pilot-flow-notification-list" aria-label="Lista de notificaciones">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $notificationTitle = $data['title'] ?? 'Nueva notificación';
                    $notificationMessage = $data['message'] ?? 'Abre el aviso para ver más detalles.';
                    $normalizedTitle = mb_strtolower($notificationTitle);
                    $notificationType = str_contains($normalizedTitle, 'resultado') ? 'results' : (str_contains($normalizedTitle, 'horario') || str_contains($normalizedTitle, 'cambio') ? 'info' : (str_contains($normalizedTitle, 'inscripción') || str_contains($normalizedTitle, 'recordatorio') ? 'reminder' : 'event'));
                @endphp
                <a class="pilot-flow-notification-card" href="{{ route('notifications.read', $notification->id) }}">
                    <span class="pilot-flow-notification-icon notification-{{ $notificationType }}" aria-hidden="true">{{ $notificationType === 'results' ? '♜' : ($notificationType === 'info' ? 'i' : ($notificationType === 'reminder' ? '♧' : '▣')) }}</span>
                    <span class="pilot-flow-notification-copy"><strong>{{ $notificationTitle }}</strong><span>{{ $notificationMessage }}</span><small>{{ $notification->created_at?->diffForHumans() }}</small></span>
                </a>
            @empty
                <div class="pilot-flow-notifications-empty"><span aria-hidden="true">♧</span><strong>No tienes notificaciones todavía</strong></div>
            @endforelse
        </section>
    </div>
</x-app-layout>