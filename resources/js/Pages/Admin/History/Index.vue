<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    entries: { type: Array, default: () => [] },
    exits:   { type: Array, default: () => [] },
});

const activeTab = ref('entries');

const searchEntries = ref('');
const searchExits   = ref('');

const filteredEntries = computed(() => {
    const q = searchEntries.value.trim().toUpperCase();
    if (!q) return props.entries;
    return props.entries.filter(s =>
        s.license_plate?.includes(q) ||
        s.parking_name?.toUpperCase().includes(q) ||
        s.agent_name?.toUpperCase().includes(q)
    );
});

const filteredExits = computed(() => {
    const q = searchExits.value.trim().toUpperCase();
    if (!q) return props.exits;
    return props.exits.filter(s =>
        s.license_plate?.includes(q) ||
        s.parking_name?.toUpperCase().includes(q) ||
        s.closer_name?.toUpperCase().includes(q)
    );
});

// ── Pagination ────────────────────────────────────────────────────────────
const PER_PAGE = 10;
const pageEntries = ref(1);
const pageExits   = ref(1);

const totalPagesEntries = computed(() => Math.max(1, Math.ceil(filteredEntries.value.length / PER_PAGE)));
const totalPagesExits   = computed(() => Math.max(1, Math.ceil(filteredExits.value.length / PER_PAGE)));

const pagedEntries = computed(() => {
    const start = (pageEntries.value - 1) * PER_PAGE;
    return filteredEntries.value.slice(start, start + PER_PAGE);
});
const pagedExits = computed(() => {
    const start = (pageExits.value - 1) * PER_PAGE;
    return filteredExits.value.slice(start, start + PER_PAGE);
});

watch(searchEntries, () => { pageEntries.value = 1; });
watch(searchExits,   () => { pageExits.value   = 1; });

// ── Stats ─────────────────────────────────────────────────────────────────
const today = new Date().toLocaleDateString('fr-FR');

const todayEntries = computed(() =>
    props.entries.filter(s => s.started_at && new Date(s.started_at).toLocaleDateString('fr-FR') === today)
);
const todayExits = computed(() =>
    props.exits.filter(s => s.ended_at && new Date(s.ended_at).toLocaleDateString('fr-FR') === today)
);
const todayRevenue = computed(() =>
    props.exits
        .filter(s => s.ended_at && new Date(s.ended_at).toLocaleDateString('fr-FR') === today)
        .reduce((sum, s) => sum + (s.amount ?? 0), 0)
);
const totalRevenue = computed(() =>
    props.exits.reduce((sum, s) => sum + (s.amount ?? 0), 0)
);

// ── Formatage ─────────────────────────────────────────────────────────────
const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatDuration = (minutes) => {
    if (!minutes && minutes !== 0) return '—';
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m ? `${h}h ${m}min` : `${h}h`;
};
</script>

<template>
    <Head title="Historique Entrées / Sorties" />

    <AuthenticatedLayout>
        <template #header>Historique Entrées / Sorties</template>

        <!-- En-tête -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Historique Entrées / Sorties</h2>
                <p class="text-sm text-gray-500 mt-1">Vue globale de toutes les sessions de stationnement en temps réel.</p>
            </div>
            <!-- Indicateur live -->
            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-emerald-700">Données en temps réel</span>
            </div>
        </div>

        <!-- Cartes stats ─────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Entrées actives -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Véhicules présents</p>
                    <p class="text-2xl font-black text-blue-700">{{ entries.length }}</p>
                </div>
            </div>

            <!-- Sorties aujourd'hui -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Sorties aujourd'hui</p>
                    <p class="text-2xl font-black text-orange-700">{{ todayExits.length }}</p>
                </div>
            </div>

            <!-- Recettes aujourd'hui -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Recettes du jour</p>
                    <p class="text-xl font-black text-emerald-700">{{ todayRevenue.toLocaleString('fr-FR') }} <span class="text-xs font-semibold">FCFA</span></p>
                </div>
            </div>

            <!-- Recettes totales -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total encaissé</p>
                    <p class="text-xl font-black text-purple-700">{{ totalRevenue.toLocaleString('fr-FR') }} <span class="text-xs font-semibold">FCFA</span></p>
                </div>
            </div>
        </div>

        <!-- Onglets ──────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Tabs header -->
            <div class="flex border-b border-gray-100">
                <button
                    @click="activeTab = 'entries'"
                    :class="[
                        'flex items-center gap-2 px-6 py-4 text-sm font-bold transition-colors duration-200 border-b-2 -mb-px',
                        activeTab === 'entries'
                            ? 'border-blue-600 text-blue-700 bg-blue-50/50'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                    ]"
                >
                    <!-- Icône entrée -->
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Entrées actives
                    <span class="px-2 py-0.5 rounded-full text-xs font-black"
                        :class="activeTab === 'entries' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'">
                        {{ entries.length }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'exits'"
                    :class="[
                        'flex items-center gap-2 px-6 py-4 text-sm font-bold transition-colors duration-200 border-b-2 -mb-px',
                        activeTab === 'exits'
                            ? 'border-orange-500 text-orange-600 bg-orange-50/50'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                    ]"
                >
                    <!-- Icône sortie -->
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sorties
                    <span class="px-2 py-0.5 rounded-full text-xs font-black"
                        :class="activeTab === 'exits' ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600'">
                        {{ exits.length }}
                    </span>
                </button>
            </div>

            <!-- ── Onglet ENTRÉES ──────────────────────────────────────── -->
            <div v-show="activeTab === 'entries'" class="p-5">

                <!-- Barre de recherche -->
                <div class="relative mb-5">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        v-model="searchEntries"
                        type="text"
                        placeholder="Rechercher par plaque, parking, agent…"
                        class="w-full pl-11 pr-4 py-3 text-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-2xl border border-gray-100">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Plaque</th>
                                <th class="px-5 py-3.5">Véhicule</th>
                                <th class="px-5 py-3.5">Parking</th>
                                <th class="px-5 py-3.5">Agent</th>
                                <th class="px-5 py-3.5">Entrée</th>
                                <th class="px-5 py-3.5">Durée</th>
                                <th class="px-5 py-3.5">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filteredEntries.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400 text-sm">
                                    Aucune entrée active en ce moment.
                                </td>
                            </tr>
                            <tr
                                v-for="s in pagedEntries"
                                :key="s.id"
                                class="hover:bg-blue-50/30 transition-colors duration-150"
                            >
                                <!-- Plaque -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-black tracking-widest font-mono">
                                        {{ s.license_plate }}
                                    </span>
                                </td>
                                <!-- Véhicule -->
                                <td class="px-5 py-4 text-gray-700">
                                    <span v-if="s.marque || s.modele">{{ [s.marque, s.modele].filter(Boolean).join(' ') }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <!-- Parking -->
                                <td class="px-5 py-4 font-medium text-gray-900">{{ s.parking_name ?? '—' }}</td>
                                <!-- Agent -->
                                <td class="px-5 py-4 text-gray-600">{{ s.agent_name ?? '—' }}</td>
                                <!-- Entrée -->
                                <td class="px-5 py-4 text-gray-600 whitespace-nowrap">{{ formatDate(s.started_at) }}</td>
                                <!-- Durée -->
                                <td class="px-5 py-4 text-gray-600">{{ formatDuration(s.duration_minutes) }}</td>
                                <!-- Statut -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                        En cours
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    :current-page="pageEntries"
                    :total-pages="totalPagesEntries"
                    :total-items="filteredEntries.length"
                    @page-change="pageEntries = $event"
                />
            </div>

            <!-- ── Onglet SORTIES ──────────────────────────────────────── -->
            <div v-show="activeTab === 'exits'" class="p-5">

                <!-- Barre de recherche -->
                <div class="relative mb-5">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        v-model="searchExits"
                        type="text"
                        placeholder="Rechercher par plaque, parking, caissier…"
                        class="w-full pl-11 pr-4 py-3 text-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    />
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-2xl border border-gray-100">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Plaque</th>
                                <th class="px-5 py-3.5">Véhicule</th>
                                <th class="px-5 py-3.5">Parking</th>
                                <th class="px-5 py-3.5">Entrée</th>
                                <th class="px-5 py-3.5">Sortie</th>
                                <th class="px-5 py-3.5">Durée</th>
                                <th class="px-5 py-3.5">Montant</th>
                                <th class="px-5 py-3.5">Caissier</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filteredExits.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center text-gray-400 text-sm">
                                    Aucune sortie enregistrée.
                                </td>
                            </tr>
                            <tr
                                v-for="s in pagedExits"
                                :key="s.id"
                                class="hover:bg-orange-50/30 transition-colors duration-150"
                            >
                                <!-- Plaque -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-black tracking-widest font-mono">
                                        {{ s.license_plate }}
                                    </span>
                                </td>
                                <!-- Véhicule -->
                                <td class="px-5 py-4 text-gray-700">
                                    <span v-if="s.marque || s.modele">{{ [s.marque, s.modele].filter(Boolean).join(' ') }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <!-- Parking -->
                                <td class="px-5 py-4 font-medium text-gray-900">{{ s.parking_name ?? '—' }}</td>
                                <!-- Entrée -->
                                <td class="px-5 py-4 text-gray-600 whitespace-nowrap">{{ formatDate(s.started_at) }}</td>
                                <!-- Sortie -->
                                <td class="px-5 py-4 text-gray-600 whitespace-nowrap">{{ formatDate(s.ended_at) }}</td>
                                <!-- Durée -->
                                <td class="px-5 py-4 text-gray-600">{{ formatDuration(s.duration_minutes) }}</td>
                                <!-- Montant -->
                                <td class="px-5 py-4">
                                    <span class="font-black text-emerald-700">{{ (s.amount ?? 0).toLocaleString('fr-FR') }}</span>
                                    <span class="text-xs text-gray-500 ml-1">FCFA</span>
                                </td>
                                <!-- Caissier -->
                                <td class="px-5 py-4 text-gray-600">{{ s.closer_name ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    :current-page="pageExits"
                    :total-pages="totalPagesExits"
                    :total-items="filteredExits.length"
                    @page-change="pageExits = $event"
                />
            </div>
        </div>

    </AuthenticatedLayout>
</template>
