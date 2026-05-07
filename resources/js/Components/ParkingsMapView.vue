<script setup>
/**
 * ParkingsMapView
 * Affiche une carte Google Maps avec tous les parkings marqués.
 * Chaque marqueur affiche le nom, l'adresse et les places disponibles.
 */
import { onMounted, ref } from 'vue';

const props = defineProps({
    parkings: { type: Array, default: () => [] },
    height:   { type: String, default: '500px' },
});

const emit = defineEmits(['select']);

const mapEl = ref(null);
const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

function loadGoogleMapsScript() {
    return new Promise((resolve, reject) => {
        if (window.google && window.google.maps) { resolve(); return; }
        if (document.getElementById('gmap-script')) {
            const poll = setInterval(() => {
                if (window.google && window.google.maps) { clearInterval(poll); resolve(); }
            }, 100);
            return;
        }
        const script = document.createElement('script');
        script.id = 'gmap-script';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places&language=fr`;
        script.async = true;
        script.defer = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

async function initMap() {
    await loadGoogleMapsScript();

    // Centrer sur le premier parking avec coordonnées valides, sinon Abidjan
    const validParkings = props.parkings.filter(p => p.latitude && p.longitude);
    const center = validParkings.length > 0
        ? { lat: parseFloat(validParkings[0].latitude), lng: parseFloat(validParkings[0].longitude) }
        : { lat: 5.3484, lng: -4.0088 };

    const map = new window.google.maps.Map(mapEl.value, {
        center,
        zoom: validParkings.length > 1 ? 12 : 15,
        mapTypeControl: false,
        streetViewControl: false,
    });

    const infoWindow = new window.google.maps.InfoWindow();
    const bounds = new window.google.maps.LatLngBounds();

    validParkings.forEach((parking) => {
        const available = Math.max(0, parking.capacity - (parking.active_sessions_count || 0));
        const occupied  = parking.active_sessions_count || 0;
        const pct       = parking.capacity > 0 ? Math.round(occupied / parking.capacity * 100) : 0;

        // Couleur du pin selon disponibilité
        const iconColor = available === 0 ? '#ef4444'        // rouge = complet
            : available < parking.capacity * 0.2 ? '#f59e0b' // orange = presque plein
            : '#22c55e';                                       // vert = disponible

        const svgMarker = {
            path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z',
            fillColor: iconColor,
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 1.5,
            scale: 1.8,
            anchor: new window.google.maps.Point(12, 22),
        };

        const marker = new window.google.maps.Marker({
            position: { lat: parseFloat(parking.latitude), lng: parseFloat(parking.longitude) },
            map,
            title: parking.name,
            icon: svgMarker,
            animation: window.google.maps.Animation.DROP,
        });

        bounds.extend(marker.getPosition());

        marker.addListener('click', () => {
            infoWindow.setContent(`
                <div style="min-width:220px;font-family:system-ui,sans-serif;">
                    <div style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:4px">${parking.name}</div>
                    <div style="color:#64748b;font-size:12px;margin-bottom:8px">📍 ${parking.address || '—'}</div>
                    <div style="display:flex;gap:8px;margin-bottom:6px">
                        <span style="background:#dcfce7;color:#166534;font-weight:700;padding:2px 10px;border-radius:999px;font-size:12px">${available} libre${available > 1 ? 's' : ''}</span>
                        <span style="background:#fee2e2;color:#991b1b;font-weight:700;padding:2px 10px;border-radius:999px;font-size:12px">${occupied} occupé${occupied > 1 ? 's' : ''}</span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:8px;height:6px;overflow:hidden;margin-bottom:6px">
                        <div style="background:${iconColor};height:100%;width:${pct}%;transition:width .4s"></div>
                    </div>
                    <div style="color:#94a3b8;font-size:11px">${parking.capacity} places au total · ${parking.reference || ''}</div>
                    <div style="margin-top:8px;font-size:11px;color:#3b82f6;font-weight:700">
                        ${parking.is_active ? '✅ Actif' : '🔴 Inactif'}
                    </div>
                </div>
            `);
            infoWindow.open(map, marker);
            emit('select', parking);
        });
    });

    // Ajuster les bounds si plusieurs parkings
    if (validParkings.length > 1) {
        map.fitBounds(bounds);
    }
}

onMounted(() => {
    initMap();
});
</script>

<template>
    <div ref="mapEl" :style="{ height, width: '100%', borderRadius: '1.5rem' }" class="overflow-hidden shadow border border-gray-100"></div>
</template>
