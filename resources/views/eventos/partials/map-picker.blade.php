@php
    $latitude = old('latitude', $latitude);
    $longitude = old('longitude', $longitude);
@endphp

<div class="col-md-6">
    <label class="form-label">Ubicación en el mapa</label>
    <p class="small text-muted mb-2">Haz clic en el mapa o arrastra el marcador para señalar el circuito.</p>
    <div class="event-map-wrapper">
        <div id="event-map-picker" class="event-map" role="application" aria-label="Mapa para seleccionar la ubicación del evento"></div>
    </div>
    <input id="latitude" name="latitude" type="hidden" value="{{ $latitude }}">
    <input id="longitude" name="longitude" type="hidden" value="{{ $longitude }}">
    @error('latitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('longitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const initialLatitude = Number(latitudeInput.value);
            const initialLongitude = Number(longitudeInput.value);
            const hasInitialLocation = Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);
            const initialCenter = hasInitialLocation ? [initialLatitude, initialLongitude] : [-16.2902, -63.5887];
            const initialZoom = hasInitialLocation ? 15 : 5;
            const map = L.map('event-map-picker').setView(initialCenter, initialZoom);

            const tileOptions = {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            };
            const tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', tileOptions).addTo(map);
            let fallbackLayer = null;

            tileLayer.on('tileerror', function () {
                if (!fallbackLayer) {
                    fallbackLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        ...tileOptions,
                        subdomains: ['a', 'b', 'c']
                    }).addTo(map);
                }
            });

            setTimeout(function () {
                map.invalidateSize();
            }, 150);

            let marker = hasInitialLocation ? L.marker(initialCenter, { draggable: true }).addTo(map) : null;

            const updateCoordinates = function (latLng) {
                latitudeInput.value = latLng.lat.toFixed(7);
                longitudeInput.value = latLng.lng.toFixed(7);
            };

            const placeMarker = function (latLng) {
                if (!marker) {
                    marker = L.marker(latLng, { draggable: true }).addTo(map);
                    marker.on('dragend', function () {
                        updateCoordinates(marker.getLatLng());
                    });
                } else {
                    marker.setLatLng(latLng);
                }

                updateCoordinates(latLng);
            };

            if (marker) {
                marker.on('dragend', function () {
                    updateCoordinates(marker.getLatLng());
                });
            }

            map.on('click', function (event) {
                placeMarker(event.latlng);
                map.setView(event.latlng, 15);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('event-map-modal')).show();
            });

            const modalMapElement = document.getElementById('event-map-modal-map');
            let modalMap = null;
            let modalMarker = null;

            document.getElementById('event-map-modal').addEventListener('shown.bs.modal', function () {
                const currentCoordinates = marker ? marker.getLatLng() : map.getCenter();

                if (!modalMap) {
                    modalMap = L.map(modalMapElement).setView(currentCoordinates, marker ? 15 : 5);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(modalMap);

                    modalMap.on('click', function (event) {
                        placeMarker(event.latlng);
                        modalMarker.setLatLng(event.latlng);
                        modalMap.setView(event.latlng, 15);
                    });
                } else {
                    modalMap.setView(currentCoordinates, marker ? 15 : 5);
                }

                if (!modalMarker) {
                    modalMarker = L.marker(currentCoordinates, { draggable: true }).addTo(modalMap);
                    modalMarker.on('dragend', function () {
                        placeMarker(modalMarker.getLatLng());
                    });
                } else {
                    modalMarker.setLatLng(currentCoordinates);
                }

                modalMap.invalidateSize();
            });
        });
    </script>
@endpush

<div class="modal fade" id="event-map-modal" tabindex="-1" aria-labelledby="event-map-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="event-map-modal-title">Seleccionar ubicación del circuito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="event-map-modal-map" class="event-map event-map-expanded" role="application" aria-label="Mapa ampliado para seleccionar la ubicación"></div>
            </div>
        </div>
    </div>
</div>