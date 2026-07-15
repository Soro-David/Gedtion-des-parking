import { ref } from 'vue';
import axios from 'axios';

/**
 * Composable réutilisable pour la reconnaissance de plaque d'immatriculation via le backend Laravel.
 *
 * Usage dans n'importe quel composant Vue :
 *   import { useLicensePlateOcr } from '@/Composables/useLicensePlateOcr';
 *   const { isLoading, error, extractPlate } = useLicensePlateOcr();
 *
 *   const plate = await extractPlate(file); // file : File | Blob
 */
export function useLicensePlateOcr() {
    const isLoading = ref(false);
    const error     = ref(null);

    /**
     * Envoie une image au backend pour l'analyse OCR et retourne la plaque détectée.
     *
     * @param {File|Blob} imageFile - Le fichier image à analyser.
     * @returns {Promise<string|null>} - La plaque en majuscules, ou null si non détectée.
     */
    const extractPlate = async (imageFile) => {
        isLoading.value = true;
        error.value     = null;

        try {
            const formData = new FormData();
            formData.append('image', imageFile);

            const res = await axios.post(route('api.ocr.license-plate'), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            return res.data.plate; // La clé retournée est 'plate'
        } catch (err) {
            const serverMessage = err.response?.data?.message;
            error.value = serverMessage ?? 'Erreur lors de la reconnaissance de la plaque.';
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    return { isLoading, error, extractPlate };
}
