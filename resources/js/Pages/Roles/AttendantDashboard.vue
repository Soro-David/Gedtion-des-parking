<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ }),
    },
});
</script>

<template>

    <!-- Actions et opérations -->
    <div class="grid grid-cols-1 gap-8">
        <!-- Opérations du jour (placeholder) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-5">Opérations du Jour</h3>
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="text-center p-4 bg-green-50 rounded-xl">
                    <p class="text-2xl font-extrabold text-green-700">—</p>
                    <p class="text-xs text-gray-500 mt-1">Entrées</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-xl">
                    <p class="text-2xl font-extrabold text-red-600">—</p>
                    <p class="text-xs text-gray-500 mt-1">Sorties</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-xl">
                    <p class="text-2xl font-extrabold text-blue-700">—</p>
                    <p class="text-xs text-gray-500 mt-1">Places libres</p>
                </div>
            </div>
            <div class="flex items-center justify-center h-20 text-center text-gray-400">
                <p class="text-sm italic">Module entrées/sorties — disponible prochainement</p>
            </div>
        </div>
    </div>
</template>
