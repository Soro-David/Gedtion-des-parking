import { ref } from 'vue';

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

/**
 * Attend que window.google.maps.places soit disponible (chargé par GoogleMapPicker)
 */
function waitForGoogleMaps(timeout = 8000) {
    return new Promise((resolve, reject) => {
        if (window.google?.maps?.places) {
            resolve();
            return;
        }
        // Charger le script si pas déjà fait
        if (!document.getElementById('gmap-script')) {
            const script = document.createElement('script');
            script.id = 'gmap-script';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places&language=fr`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
        const start = Date.now();
        const poll = setInterval(() => {
            if (window.google?.maps?.places) {
                clearInterval(poll);
                resolve();
            } else if (Date.now() - start > timeout) {
                clearInterval(poll);
                reject(new Error('Google Maps places timed out'));
            }
        }, 100);
    });
}

export function usePlacesAutocomplete() {
    const searchQuery   = ref('');
    const suggestions   = ref([]);
    const showSuggestions = ref(false);
    const isSearching   = ref(false);

    let autocompleteService = null;
    let placesService       = null;
    let debounceTimer       = null;

    async function initServices() {
        if (autocompleteService) return;
        await waitForGoogleMaps();
        autocompleteService = new window.google.maps.places.AutocompleteService();
        const dummyEl = document.createElement('div');
        placesService = new window.google.maps.places.PlacesService(dummyEl);
    }

    async function onInput() {
        clearTimeout(debounceTimer);
        const q = searchQuery.value.trim();
        if (!q) {
            suggestions.value   = [];
            showSuggestions.value = false;
            return;
        }
        debounceTimer = setTimeout(async () => {
            isSearching.value = true;
            try {
                await initServices();
                autocompleteService.getPlacePredictions(
                    { input: q, language: 'fr' },
                    (predictions, status) => {
                        isSearching.value = false;
                        if (
                            status === window.google.maps.places.PlacesServiceStatus.OK &&
                            predictions?.length
                        ) {
                            suggestions.value   = predictions;
                            showSuggestions.value = true;
                        } else {
                            suggestions.value   = [];
                            showSuggestions.value = false;
                        }
                    },
                );
            } catch {
                isSearching.value = false;
                suggestions.value   = [];
                showSuggestions.value = false;
            }
        }, 320);
    }

    /**
     * Sélectionne une suggestion et retourne { lat, lng, address }
     */
    function selectSuggestion(place) {
        return new Promise(async (resolve, reject) => {
            searchQuery.value     = place.description;
            suggestions.value     = [];
            showSuggestions.value = false;
            try {
                await initServices();
                placesService.getDetails(
                    { placeId: place.place_id, fields: ['geometry', 'formatted_address', 'name'] },
                    (result, status) => {
                        if (status === window.google.maps.places.PlacesServiceStatus.OK) {
                            resolve({
                                lat:     result.geometry.location.lat().toFixed(7),
                                lng:     result.geometry.location.lng().toFixed(7),
                                address: result.formatted_address || place.description,
                            });
                        } else {
                            reject(new Error('Lieu introuvable'));
                        }
                    },
                );
            } catch (e) {
                reject(e);
            }
        });
    }

    function clearSuggestions() {
        suggestions.value     = [];
        showSuggestions.value = false;
    }

    return {
        searchQuery,
        suggestions,
        showSuggestions,
        isSearching,
        onInput,
        selectSuggestion,
        clearSuggestions,
    };
}
