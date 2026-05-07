import { ref } from 'vue';
import { GoogleGenerativeAI } from '@google/generative-ai';

const genAI = new GoogleGenerativeAI(import.meta.env.VITE_GEMINI_API_KEY);

/**
 * Composable réutilisable pour la reconnaissance de plaque d'immatriculation via Gemini.
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
     * Convertit un File/Blob en base64 (sans le préfixe data URL).
     *
     * @param {File|Blob} file
     * @returns {Promise<string>}
     */
    const toBase64 = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload  = () => resolve(reader.result.split(',')[1]);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });

    /**
     * Envoie une image à Gemini et retourne la plaque détectée.
     *
     * @param {File|Blob} imageFile - Le fichier image à analyser.
     * @returns {Promise<string|null>} - La plaque en majuscules, ou null si non détectée.
     */
    const extractPlate = async (imageFile) => {
        isLoading.value = true;
        error.value     = null;

        try {
            const base64    = await toBase64(imageFile);
            const mimeType  = imageFile.type || 'image/jpeg';
            const model     = genAI.getGenerativeModel({ model: 'gemini-2.5-flash' });

            const result = await model.generateContent([
                {
                    inlineData: { data: base64, mimeType },
                },
                'Extract the vehicle license plate number from this image. '
                    + 'Return ONLY the plate number in uppercase, without spaces, dashes or any explanation. '
                    + 'If no plate is visible or readable, return exactly: NOT_FOUND',
            ]);

            const raw = result.response.text().trim().toUpperCase().replace(/[\s\-.]+/g, '');

            return raw === '' || raw === 'NOT_FOUND' ? null : raw;
        } catch (err) {
            error.value = err.message ?? 'Erreur lors de la reconnaissance de la plaque.';
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    return { isLoading, error, extractPlate };
}
