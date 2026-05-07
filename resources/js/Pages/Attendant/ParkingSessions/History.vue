<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    sessions: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

// Filtre
const search = ref('');

const filtered = computed(() => {
    const q = search.value.trim().toUpperCase();
    if (!q) return props.sessions;
    return props.sessions.filter(s => s.license_plate.includes(q));
});

// Stats du jour
const today = new Date().toLocaleDateString('fr-FR');
const todaySessions = computed(() =>
    props.sessions.filter(s => s.ended_at && new Date(s.ended_at).toLocaleDateString('fr-FR') === today)
);
const todayTotal = computed(() =>
    todaySessions.value.reduce((sum, s) => sum + (s.amount ?? 0), 0)
);

// Formatage
const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatDuration = (minutes) => {
    if (!minutes) return '—';
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m ? `${h}h ${m}min` : `${h}h`;
};
</script>

<template>
    <Head title="Historique sortie" />

    <AuthenticatedLayout>
        <template #header>Historique des sorties</template>

        <!-- Flash success -->
        <div v-if="flash.success" class="mb-6 px-5 py-3 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <!-- Entête + stats -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Historique des sorties</h2>
                <p class="text-sm text-gray-500 mt-1">Toutes les sorties que vous avez enregistrées.</p>
            </div>
        </div>

        <!-- Cartes stats du jour -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Sorties aujourd'hui</p>
                    <p class="text-2xl font-black text-gray-900">{{ todaySessions.length }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-green-50 text-green-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Montant encaissé aujourd'hui</p>
                    <p class="text-2xl font-black text-green-700">{{ todayTotal.toLocaleString('fr-FR') }} <span class="text-sm font-semibold">FCFA</span></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total historique</p>
                    <p class="text-2xl font-black text-gray-900">{{ sessions.length }}</p>
                </div>
            </div>
        </div>

        <!-- Filtre -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Filtrer par immatriculation…"
                    class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm uppercase font-semibold tracking-widest placeholder:normal-case placeholder:font-normal placeholder:tracking-normal focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none"
                />
            </div>
        </div>

        <!-- Tableau -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <span class="text-sm font-semibold text-gray-700">
                    <span class="text-gray-900 font-bold">{{ filtered.length }}</span> sortie(s)
                    <span v-if="search" class="text-gray-500"> pour « <span class="uppercase tracking-widest text-gray-800">{{ search }}</span> »</span>
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Immatriculation</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Véhicule</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Parking</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Entrée</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sortie</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Durée</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="(s, index) in filtered"
                            :key="s.id"
                            class="hover:bg-gray-50/50 transition-colors"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-semibold">{{ index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-gray-100 text-gray-800 border border-gray-200 uppercase tracking-widest shadow-sm">
                                    {{ s.license_plate }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <span v-if="s.marque || s.modele">{{ [s.marque, s.modele].filter(Boolean).join(' ') }}</span>
                                <span v-else class="text-gray-400 italic">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ s.parking_name || '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(s.started_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(s.ended_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">{{ formatDuration(s.duration_minutes) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span v-if="s.amount !== null" class="font-bold text-green-700">
                                    {{ Number(s.amount).toLocaleString('fr-FR') }}
                                    <span class="text-xs font-semibold text-green-600">FCFA</span>
                                </span>
                                <span v-else class="text-xs text-gray-400 italic">—</span>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0 && sessions.length > 0">
                            <td colspan="8" class="px-6 py-16 text-center text-gray-400 italic">
                                Aucune sortie trouvée pour « {{ search }} ».
                            </td>
                        </tr>
                        <tr v-if="sessions.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <span class="font-medium">Aucune sortie enregistrée pour le moment.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
