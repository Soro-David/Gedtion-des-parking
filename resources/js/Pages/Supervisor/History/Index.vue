<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    entries:  { type: Array, default: () => [] },
    exits:    { type: Array, default: () => [] },
    parkings: { type: Array, default: () => [] },
});

const activeTab    = ref('entries'); // 'entries' | 'exits' | 'parkings'
const searchText   = ref('');
const searchParking = ref('');

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

const filteredParkings = computed(() =>
    props.parkings.filter(p =>
        !searchParking.value ||
        p.name?.toLowerCase().includes(searchParking.value.toLowerCase()) ||
        p.address?.toLowerCase().includes(searchParking.value.toLowerCase())
    )
);

// ── Pagination ──────────────────────────────────────────────────────
const PER_PAGE = 10;
const pageEntries  = ref(1);
const pageExits    = ref(1);
const pageParkings = ref(1);

const totalPagesEntries  = computed(() => Math.max(1, Math.ceil(filteredEntries.value.length / PER_PAGE)));
const totalPagesExits    = computed(() => Math.max(1, Math.ceil(filteredExits.value.length / PER_PAGE)));
const totalPagesParkings = computed(() => Math.max(1, Math.ceil(filteredParkings.value.length / PER_PAGE)));

const pagedEntries  = computed(() => { const s = (pageEntries.value - 1) * PER_PAGE;  return filteredEntries.value.slice(s, s + PER_PAGE); });
const pagedExits    = computed(() => { const s = (pageExits.value - 1) * PER_PAGE;    return filteredExits.value.slice(s, s + PER_PAGE); });
const pagedParkings = computed(() => { const s = (pageParkings.value - 1) * PER_PAGE; return filteredParkings.value.slice(s, s + PER_PAGE); });

watch(searchText,   () => { pageEntries.value = 1; pageExits.value = 1; });
watch(searchParking, () => { pageParkings.value = 1; });

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
    <Head title="Historique Entrées/Sorties" />

    <AuthenticatedLayout>
        <template #header>Historique Entrées / Sorties</template>

        <!-- Tab Bar -->
        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-2xl mb-8 w-fit">
            <button
                v-for="tab in [
                    { id: 'entries',  label: 'Entrées',  count: entries.length,  color: 'blue' },
                    { id: 'exits',    label: 'Sorties',  count: exits.length,    color: 'orange' },
                    { id: 'parkings', label: 'Parkings', count: parkings.length, color: 'emerald' },
                ]"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2',
                    activeTab === tab.id ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-800',
                ]"
            >
                {{ tab.label }}
                <span :class="[
                    'text-xs px-1.5 py-0.5 rounded-full font-bold',
                    activeTab === tab.id
                        ? (tab.color === 'blue' ? 'bg-blue-100 text-blue-700' : tab.color === 'orange' ? 'bg-orange-100 text-orange-700' : 'bg-emerald-100 text-emerald-700')
                        : 'bg-gray-200 text-gray-500'
                ]">{{ tab.count }}</span>
            </button>
        </div>

        <!-- ── ENTRÉES ──────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'entries'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Véhicules actuellement garés</h2>
                    <p class="text-sm text-gray-400">Sessions en cours dans tous les parkings.</p>
                </div>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="searchText" type="text" placeholder="Plaque, parking, agent..." class="pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 w-64"/>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-blue-50/60">
                                <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Plaque</th>
                                <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Véhicule</th>
                                <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Parking</th>
                                <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Entrée</th>
                                <th class="px-6 py-4 text-xs font-bold text-blue-700 uppercase tracking-wider">Durée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="e in pagedEntries" :key="e.id" class="hover:bg-blue-50/20 transition-colors">
                                <td class="px-6 py-4"><span class="font-mono font-bold text-sm bg-gray-100 px-2 py-1 rounded-lg">{{ e.license_plate }}</span></td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ [e.marque, e.modele].filter(Boolean).join(' ') || '—' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ e.parking_name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ e.agent_name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(e.started_at) }}</td>
                                <td class="px-6 py-4"><span class="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-1 rounded-full">{{ formatDuration(e.duration_minutes) }}</span></td>
                            </tr>
                            <tr v-if="filteredEntries.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center text-gray-400 text-sm">Aucune entrée active.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    :current-page="pageEntries"
                    :total-pages="totalPagesEntries"
                    :total-items="filteredEntries.length"
                    class="px-2"
                    @page-change="pageEntries = $event"
                />
            </div>
        </div>

        <!-- ── SORTIES ──────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'exits'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Historique des sorties</h2>
                    <p class="text-sm text-gray-400">Toutes les sessions clôturées.</p>
                </div>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="searchText" type="text" placeholder="Plaque, parking, agent..." class="pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-300 w-64"/>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-orange-50/60">
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
                            <tr v-for="e in pagedExits" :key="e.id" class="hover:bg-orange-50/20 transition-colors">
                                <td class="px-6 py-4"><span class="font-mono font-bold text-sm bg-gray-100 px-2 py-1 rounded-lg">{{ e.license_plate }}</span></td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ [e.marque, e.modele].filter(Boolean).join(' ') || '—' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ e.parking_name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ e.agent_name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(e.started_at) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(e.ended_at) }}</td>
                                <td class="px-6 py-4"><span class="text-xs font-semibold bg-orange-100 text-orange-700 px-2 py-1 rounded-full">{{ formatDuration(e.duration_minutes) }}</span></td>
                                <td class="px-6 py-4 text-xs font-bold text-emerald-700">{{ formatCFA(e.amount) }}</td>
                            </tr>
                            <tr v-if="filteredExits.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center text-gray-400 text-sm">Aucune sortie trouvée.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    :current-page="pageExits"
                    :total-pages="totalPagesExits"
                    :total-items="filteredExits.length"
                    class="px-2"
                    @page-change="pageExits = $event"
                />
            </div>
        </div>

        <!-- ── PARKINGS ─────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'parkings'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Parkings — disponibilité & position</h2>
                    <p class="text-sm text-gray-400">Nombre de places disponibles en temps réel et localisation GPS.</p>
                </div>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="searchParking" type="text" placeholder="Nom, adresse..." class="pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-300 w-64"/>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="p in pagedParkings"
                    :key="p.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                >
                    <div class="flex items-start justify-between p-5 pb-3">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <div class="p-2.5 rounded-xl bg-emerald-50 flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-gray-900 truncate">{{ p.name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ p.address || 'Adresse non renseignée' }}</p>
                            </div>
                        </div>
                        <span :class="['flex-shrink-0 text-xs font-bold px-2 py-1 rounded-full border ml-2', p.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200']">
                            {{ p.is_active ? 'Actif' : 'Fermé' }}
                        </span>
                    </div>

                    <div class="px-5 pb-2">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Occupation</span>
                            <span>{{ p.occupied_spots }} / {{ p.capacity }}</span>
                        </div>
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                :style="{ width: p.capacity > 0 ? ((p.occupied_spots / p.capacity) * 100) + '%' : '0%' }"
                                :class="['h-full rounded-full transition-all duration-500', p.capacity > 0 && (p.occupied_spots / p.capacity) > 0.9 ? 'bg-red-500' : p.capacity > 0 && (p.occupied_spots / p.capacity) > 0.6 ? 'bg-amber-500' : 'bg-emerald-500']"
                            ></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 mt-3">
                        <div class="px-5 py-3 text-center">
                            <p class="text-xs text-gray-500 mb-0.5">Disponibles</p>
                            <p :class="['text-xl font-extrabold', p.available_spots <= 0 ? 'text-red-600' : p.available_spots <= p.capacity * 0.4 ? 'text-amber-600' : 'text-emerald-600']">
                                {{ p.available_spots }}
                            </p>
                        </div>
                        <div class="px-5 py-3 text-center">
                            <p class="text-xs text-gray-500 mb-0.5">Capacité</p>
                            <p class="text-xl font-extrabold text-gray-800">{{ p.capacity }}</p>
                        </div>
                    </div>

                    <div v-if="p.latitude && p.longitude" class="border-t border-gray-100 px-5 py-3">
                        <a
                            :href="`https://www.google.com/maps?q=${p.latitude},${p.longitude}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ p.latitude.toFixed(4) }}, {{ p.longitude.toFixed(4) }} — Voir sur Maps
                        </a>
                    </div>
                    <div v-else class="border-t border-gray-100 px-5 py-3">
                        <p class="text-xs text-gray-400 italic">Position GPS non renseignée</p>
                    </div>
                </div>

                <div v-if="filteredParkings.length === 0" class="col-span-full py-20 text-center text-gray-400">
                    Aucun parking trouvé.
                </div>
            </div>
            <Pagination
                :current-page="pageParkings"
                :total-pages="totalPagesParkings"
                :total-items="filteredParkings.length"
                class="px-2 mt-2"
                @page-change="pageParkings = $event"
            />
        </div>

    </AuthenticatedLayout>
</template>
