<script setup>
/**
 * GoogleMapPicker
 * Composant interactif Google Maps pour choisir une position.
 * Émet `update:lat` et `update:lng` quand l'utilisateur clique sur la carte.
 */
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    lat: { type: [Number, String], default: null },
    lng: { type: [Number, String], default: null },
    height: { type: String, default: '320px' },
});

const emit = defineEmits(['update:lat', 'update:lng']);

const mapEl = ref(null);
let map = null;
let marker = null;

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

// Charge le script Google Maps une seule fois dans la page
function loadGoogleMapsScript() {
    return new Promise((resolve, reject) => {
        if (window.google && window.google.maps) {
            resolve();
            return;
        }
        if (document.getElementById('gmap-script')) {
            // Déjà en cours de chargement – attendre
            const poll = setInterval(() => {
                if (window.google && window.google.maps) {
                    clearInterval(poll);
                    resolve();
                }
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

    const defaultCenter = {
        lat: props.lat ? parseFloat(props.lat) : 5.3484,   // Abidjan par défaut
        lng: props.lng ? parseFloat(props.lng) : -4.0088,
    };

    map = new window.google.maps.Map(mapEl.value, {
        center: defaultCenter,
        zoom: props.lat ? 16 : 13,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        styles: [
            { featureType: 'poi.business', stylers: [{ visibility: 'simplified' }] },
        ],
    });

    // Marker déplaçable
    marker = new window.google.maps.Marker({
        position: defaultCenter,
        map,
        draggable: true,
        title: 'Position du parking',
        animation: window.google.maps.Animation.DROP,
    });

    // Si pas de coordonnées initiales, cacher le marker
    if (!props.lat || !props.lng) {
        marker.setVisible(false);
    }

    // Clic sur la carte → déplacer le marker
    map.addListener('click', (e) => {
        const lat = e.latLng.lat().toFixed(7);
        const lng = e.latLng.lng().toFixed(7);
        marker.setPosition(e.latLng);
        marker.setVisible(true);
        emit('update:lat', lat);
        emit('update:lng', lng);
    });

    // Glisser le marker
    marker.addListener('dragend', (e) => {
        const lat = e.latLng.lat().toFixed(7);
        const lng = e.latLng.lng().toFixed(7);
        emit('update:lat', lat);
        emit('update:lng', lng);
    });
}

// Quand les coordonnées changent depuis l'extérieur (ex: "Localiser ma position")
watch([() => props.lat, () => props.lng], ([newLat, newLng]) => {
    if (!map || !marker || !newLat || !newLng) return;
    const pos = { lat: parseFloat(newLat), lng: parseFloat(newLng) };
    map.panTo(pos);
    map.setZoom(17);
    marker.setPosition(pos);
    marker.setVisible(true);
});

onMounted(() => {
    initMap();
});

onUnmounted(() => {
    map = null;
    marker = null;
});
</script>

<template>
    <div
        ref="mapEl"
        :style="{ height, width: '100%', borderRadius: '1rem' }"
        class="overflow-hidden shadow-inner border border-gray-100"
    ></div>
</template>
