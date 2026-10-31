@if($event->latitude !== null && $event->longitude !== null)
    @php($showInlineMap = $showInlineMap ?? true)

    @if($showInlineMap)
        <div id="event-map-viewer" class="event-map event-map-viewer event-map-clickable" role="button" tabindex="0" aria-label="Hacer clic para ampliar el mapa con la ubicación del circuito"></div>
    @endif

    <div class="modal fade" id="event-map-viewer-modal" tabindex="-1" aria-labelledby="event-map-viewer-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="event-map-viewer-modal-title">Ubicación del circuito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="event-map-viewer-modal-map" class="event-map event-map-expanded" role="img" aria-label="Mapa ampliado con la ubicación del circuito"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const coordinates = [{{ $event->latitude }}, {{ $event->longitude }}];
                const inlineMap = document.getElementById('event-map-viewer');

                if (inlineMap) {
                    const map = L.map(inlineMap).setView(coordinates, 15);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(map);

                    setTimeout(function () {
                        map.invalidateSize();
                    }, 150);

                    L.marker(coordinates).addTo(map).bindPopup(@json($event->location)).openPopup();
                    inlineMap.addEventListener('click', openExpandedMap);
                    inlineMap.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            openExpandedMap();
                        }
                    });
                }

                const modal = document.getElementById('event-map-viewer-modal');
                let expandedMap = null;

                const openExpandedMap = function () {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                };

                modal.addEventListener('shown.bs.modal', function () {
                    if (!expandedMap) {
                        expandedMap = L.map('event-map-viewer-modal-map').setView(coordinates, 15);
                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 19
                        }).addTo(expandedMap);
                        L.marker(coordinates).addTo(expandedMap).bindPopup(@json($event->location)).openPopup();
                    }

                    expandedMap.invalidateSize();
                    expandedMap.setView(coordinates, 15);
                });
            });
        </script>
    @endpush
@endif