import { ref } from 'vue';

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

export function useReverseGeocode() {
    const locationName = ref('');

    const reverseGeocode = async (lat, lng) => {
        locationName.value = '';
        if (!lat || !lng) return;
        try {
            if (GOOGLE_MAPS_API_KEY) {
                const res = await fetch(
                    `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&language=fr&key=${GOOGLE_MAPS_API_KEY}`
                );
                const data = await res.json();
                if (data.status === 'OK' && data.results && data.results.length > 0) {
                    locationName.value = data.results[0].formatted_address;
                    return;
                }
            }
            // Fallback Nominatim
            const res = await fetch(
                `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=fr`
            );
            const data = await res.json();
            const a = data.address || {};
            const parts = [
                a.road || a.pedestrian || a.footway || a.path || a.residential || a.amenity,
                a.neighbourhood || a.quarter || a.suburb || a.hamlet,
                a.city_district || a.district || a.borough,
                a.city || a.town || a.village || a.county,
                a.state,
            ].filter(Boolean);
            locationName.value = parts.length > 0
                ? parts.slice(0, 4).join(', ')
                : (data.display_name || '').split(',').slice(0, 3).join(', ');
        } catch {
            locationName.value = '';
        }
    };

    return { locationName, reverseGeocode };
}
