import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Rafraîchit automatiquement les props Inertia de la page courante.
 *
 * @param {number} intervalMs   Intervalle en millisecondes (défaut : 30 000 ms)
 * @param {string[]} only       Props à recharger (optionnel, recharge tout si vide)
 */
export function useAutoRefresh(intervalMs = 30_000, only = []) {
    let timer = null;

    const refresh = () => {
        const options = {
            preserveScroll: true,
            preserveState: true,
        };
        if (only.length > 0) {
            options.only = only;
        }
        router.reload(options);
    };

    onMounted(() => {
        timer = setInterval(refresh, intervalMs);
    });

    onUnmounted(() => {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    });
}
