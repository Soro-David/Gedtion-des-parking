<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    entries: { type: Array, default: () => [] },
    exits:   { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

// Onglet actif
const tab = ref('entries');

// Filtre
const search = ref('');

// Pagination
const PER_PAGE = 10;
const entryPage = ref(1);
const exitPage  = ref(1);

watch(search, () => { entryPage.value = 1; exitPage.value = 1; });
watch(tab,    () => { entryPage.value = 1; exitPage.value = 1; });

const filteredEntries = computed(() => {
    const q = search.value.trim().toUpperCase();
    if (!q) return props.entries;
    return props.entries.filter(s => s.license_plate.includes(q));
});

const filteredExits = computed(() => {
    const q = search.value.trim().toUpperCase();
    if (!q) return props.exits;
    return props.exits.filter(s => s.license_plate.includes(q));
});

const pagedEntries = computed(() =>
    filteredEntries.value.slice((entryPage.value - 1) * PER_PAGE, entryPage.value * PER_PAGE)
);
const entryTotalPages = computed(() => Math.max(1, Math.ceil(filteredEntries.value.length / PER_PAGE)));

const pagedExits = computed(() =>
    filteredExits.value.slice((exitPage.value - 1) * PER_PAGE, exitPage.value * PER_PAGE)
);
const exitTotalPages = computed(() => Math.max(1, Math.ceil(filteredExits.value.length / PER_PAGE)));

// Stats du jour
const today = new Date().toLocaleDateString('fr-FR');
const todayEntries = computed(() =>
    props.entries.filter(s => s.started_at && new Date(s.started_at).toLocaleDateString('fr-FR') === today)
);
const todayExits = computed(() =>
    props.exits.filter(s => s.ended_at && new Date(s.ended_at).toLocaleDateString('fr-FR') === today)
);
const todayAmount = computed(() =>
    todayExits.value.reduce((sum, s) => sum + (s.amount ?? 0), 0)
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

const statusLabel = (status) => status === 'occupied' ? 'En cours' : 'Terminé';
const statusClass = (status) => status === 'occupied'
    ? 'bg-green-50 text-green-700 border-green-200'
    : 'bg-gray-100 text-gray-600 border-gray-200';
</script>

<template>
    <Head title="Historique" />

    <AuthenticatedLayout>
        <template #header>Historique</template>

        <!-- Flash success -->
        <div v-if="flash.success" class="mb-6 px-5 py-3 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <!-- Titre -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Historique</h2>
            <p class="text-sm text-gray-500 mt-1">Toutes les entrées et sorties que vous avez enregistrées.</p>
        </div>

        <!-- Cartes stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Entrées aujourd'hui</p>
                    <p class="text-2xl font-black text-gray-900">{{ todayEntries.length }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Sorties aujourd'hui</p>
                    <p class="text-2xl font-black text-gray-900">{{ todayExits.length }}</p>
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
                    <p class="text-2xl font-black text-green-700">{{ todayAmount.toLocaleString('fr-FR') }} <span class="text-sm font-semibold">FCFA</span></p>
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

        <!-- Onglets -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Tab bar -->
            <div class="flex border-b border-gray-100">
                <button
                    @click="tab = 'entries'"
                    :class="['flex-1 flex items-center justify-center gap-2 px-6 py-4 text-sm font-semibold transition-colors', tab === 'entries' ? 'text-green-700 border-b-2 border-green-600 bg-green-50/40' : 'text-gray-500 hover:text-gray-700']"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14" />
                    </svg>
                    Entrées
                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold" :class="tab === 'entries' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                        {{ entries.length }}
                    </span>
                </button>
                <button
                    @click="tab = 'exits'"
                    :class="['flex-1 flex items-center justify-center gap-2 px-6 py-4 text-sm font-semibold transition-colors', tab === 'exits' ? 'text-red-700 border-b-2 border-red-500 bg-red-50/40' : 'text-gray-500 hover:text-gray-700']"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                    </svg>
                    Sorties
                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold" :class="tab === 'exits' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500'">
                        {{ exits.length }}
                    </span>
                </button>
            </div>

            <!-- Tableau Entrées -->
            <div v-if="tab === 'entries'" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Immatriculation</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Véhicule</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Parking</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Entrée</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(s, index) in pagedEntries" :key="'e-'+s.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-semibold">{{ (entryPage - 1) * PER_PAGE + index + 1 }}</td>
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border', statusClass(s.status)]">
                                    {{ statusLabel(s.status) }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="filteredEntries.length === 0 && entries.length > 0">
                            <td colspan="6" class="px-6 py-16 text-center text-gray-400 italic">Aucune entrée trouvée pour « {{ search }} ».</td>
                        </tr>
                        <tr v-if="entries.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 16l-4-4m0 0l4-4m-4 4h14" />
                                    </svg>
                                    <span class="font-medium">Aucune entrée enregistrée pour le moment.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <Pagination
                    :current-page="entryPage"
                    :total-pages="entryTotalPages"
                    :total-items="filteredEntries.length"
                    :per-page="PER_PAGE"
                    class="px-4"
                    @page-change="entryPage = $event"
                />
            </div>

            <!-- Tableau Sorties -->
            <div v-if="tab === 'exits'" class="overflow-x-auto">
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
                        <tr v-for="(s, index) in pagedExits" :key="'x-'+s.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-semibold">{{ (exitPage - 1) * PER_PAGE + index + 1 }}</td>
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
                        <tr v-if="filteredExits.length === 0 && exits.length > 0">
                            <td colspan="8" class="px-6 py-16 text-center text-gray-400 italic">Aucune sortie trouvée pour « {{ search }} ».</td>
                        </tr>
                        <tr v-if="exits.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                                    </svg>
                                    <span class="font-medium">Aucune sortie enregistrée pour le moment.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <Pagination
                    :current-page="exitPage"
                    :total-pages="exitTotalPages"
                    :total-items="filteredExits.length"
                    :per-page="PER_PAGE"
                    class="px-4"
                    @page-change="exitPage = $event"
                />
            </div>
        </div>

    </AuthenticatedLayout>
</template>
