<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    entries: { type: Array, default: () => [] },
    exits:   { type: Array, default: () => [] },
});

const activeTab  = ref('entries');
const searchText = ref('');

const filteredEntries = computed(() =>
    props.entries.filter(e =>
        !searchText.value ||
        e.license_plate?.toLowerCase().includes(searchText.value.toLowerCase()) ||
        e.parking_name?.toLowerCase().includes(searchText.value.toLowerCase()) ||
        e.agent_name?.toLowerCase().includes(searchText.value.toLowerCase())
    )
);

const filteredExits = computed(() =>
    props.exits.filter(e =>
        !searchText.value ||
        e.license_plate?.toLowerCase().includes(searchText.value.toLowerCase()) ||
        e.parking_name?.toLowerCase().includes(searchText.value.toLowerCase()) ||
        e.agent_name?.toLowerCase().includes(searchText.value.toLowerCase())
    )
);

function formatDate(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
function formatDuration(mins) {
    if (!mins && mins !== 0) return '—';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h ${m}min` : `${m}min`;
}
function formatCFA(n) {
    if (!n && n !== 0) return '—';
    return Number(n).toLocaleString('fr-FR') + ' FCFA';
}
</script>

<template>
    <Head title="Historique Entrées / Sorties" />

    <AuthenticatedLayout>
        <template #header>Historique Entrées / Sorties</template>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <p class="text-sm text-gray-500 mt-1">Journal complet de tous les passages dans les parkings.</p>
            </div>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    v-model="searchText"
                    type="text"
                    placeholder="Plaque, parking, agent..."
                    class="pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#487119]/30 focus:border-[#487119] w-64"
                />
            </div>
        </div>

        <!-- Sub-tabs -->
        <div class="flex gap-2 mb-6">
            <button
                @click="activeTab = 'entries'"
                :class="['flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 border', activeTab === 'entries' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-500 border-gray-200 hover:border-blue-300']"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Entrées
                <span :class="['text-xs px-1.5 py-0.5 rounded-full font-bold', activeTab === 'entries' ? 'bg-blue-500 text-white' : 'bg-blue-100 text-blue-700']">{{ filteredEntries.length }}</span>
            </button>
            <button
                @click="activeTab = 'exits'"
                :class="['flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 border', activeTab === 'exits' ? 'bg-orange-500 text-white border-orange-500 shadow-sm' : 'bg-white text-gray-500 border-gray-200 hover:border-orange-300']"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sorties
                <span :class="['text-xs px-1.5 py-0.5 rounded-full font-bold', activeTab === 'exits' ? 'bg-orange-400 text-white' : 'bg-orange-100 text-orange-700']">{{ filteredExits.length }}</span>
            </button>
        </div>

        <!-- Entrées Table -->
        <div v-if="activeTab === 'entries'" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-blue-50/60">
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Plaque</th>
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Véhicule</th>
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Parking</th>
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Agent</th>
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Entrée</th>
                            <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Durée</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(entry, i) in filteredEntries" :key="entry.id" class="hover:bg-blue-50/30 transition-colors">
                            <td class="px-6 py-4 text-xs text-gray-400">{{ i + 1 }}</td>
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-sm bg-gray-100 px-2 py-1 rounded-lg text-gray-800">{{ entry.license_plate }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ [entry.marque, entry.modele].filter(Boolean).join(' ') || '—' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ entry.parking_name || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ entry.agent_name || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(entry.started_at) }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-1 rounded-full">{{ formatDuration(entry.duration_minutes) }}</span>
                            </td>
                        </tr>
                        <tr v-if="filteredEntries.length === 0">
                            <td colspan="7" class="px-6 py-20 text-center text-gray-400 text-sm">Aucune entrée active.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sorties Table -->
        <div v-else class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-orange-50/60">
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Plaque</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Véhicule</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Parking</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Agent</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Entrée</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Sortie</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Durée</th>
                            <th class="px-6 py-4 text-xs font-bold text-orange-700 uppercase tracking-wider">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(exit, i) in filteredExits" :key="exit.id" class="hover:bg-orange-50/30 transition-colors">
                            <td class="px-6 py-4 text-xs text-gray-400">{{ i + 1 }}</td>
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-sm bg-gray-100 px-2 py-1 rounded-lg text-gray-800">{{ exit.license_plate }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ [exit.marque, exit.modele].filter(Boolean).join(' ') || '—' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ exit.parking_name || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ exit.agent_name || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(exit.started_at) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(exit.ended_at) }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold bg-orange-100 text-orange-700 px-2 py-1 rounded-full">{{ formatDuration(exit.duration_minutes) }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-emerald-700">{{ formatCFA(exit.amount) }}</td>
                        </tr>
                        <tr v-if="filteredExits.length === 0">
                            <td colspan="9" class="px-6 py-20 text-center text-gray-400 text-sm">Aucune sortie trouvée.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
