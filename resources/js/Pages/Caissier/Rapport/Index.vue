<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

useAutoRefresh();

const props = defineProps({
    agents:          { type: Array,  default: () => [] },
    sessions:        { type: Array,  default: () => [] },
    totalEntries:    { type: Number, default: 0 },
    totalExits:      { type: Number, default: 0 },
    totalMontant:    { type: Number, default: 0 },
    totalReverse:    { type: Number, default: 0 },
    totalNonReverse: { type: Number, default: 0 },
    filters:         { type: Object, default: () => ({ periode: 'jour', date_from: '', date_to: '', agent_id: '' }) },
});

const periode  = ref(props.filters.periode ?? 'jour');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo   = ref(props.filters.date_to   ?? '');
const agentId  = ref(props.filters.agent_id  ?? '');

function applyFilter() {
    const params = {};
    if (dateFrom.value && dateTo.value) {
        params.date_from = dateFrom.value;
        params.date_to   = dateTo.value;
    } else {
        params.periode = periode.value;
    }

    if (agentId.value) {
        params.agent_id = agentId.value;
    }

    router.get(route('caissier.rapport.index'), params, { preserveState: false });
}

function resetFilter() {
    periode.value  = 'jour';
    dateFrom.value = '';
    dateTo.value   = '';
    agentId.value  = '';
    router.get(route('caissier.rapport.index'), { periode: 'jour' }, { preserveState: false });
}

function buildExportUrl(type) {
    const base = type === 'pdf'
        ? route('caissier.rapport.export-pdf')
        : route('caissier.rapport.export-csv');

    const params = new URLSearchParams();
    if (dateFrom.value && dateTo.value) {
        params.append('date_from', dateFrom.value);
        params.append('date_to', dateTo.value);
    } else {
        params.append('periode', periode.value);
    }

    if (agentId.value) {
        params.append('agent_id', agentId.value);
    }

    return `${base}?${params.toString()}`;
}

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const periodeLabel = computed(() => {
    if (props.filters.date_from && props.filters.date_to)
        return `${props.filters.date_from} → ${props.filters.date_to}`;
    return { jour: "Aujourd'hui", mois: 'Ce mois', annee: 'Cette année' }[props.filters.periode] ?? '';
});

const agentLabel = computed(() => {
    if (!props.filters.agent_id) return 'Tous les agents';
    const match = props.agents.find(a => String(a.id) === String(props.filters.agent_id));
    return match ? `${match.name} ${match.first_name ?? ''}`.trim() : 'Agent sélectionné';
});
</script>

<template>
    <Head title="Rapport de Caisse & Agents" />

    <AuthenticatedLayout>
        <template #header>Rapport Caissier</template>

        <div class="max-w-7xl mx-auto space-y-6">

            <!-- ── Filtres ─────────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-black text-gray-800">Filtres de recherche</h2>
                    <span class="text-xs text-gray-500 font-semibold">
                        Filtré par : <strong class="text-gray-800">{{ agentLabel }}</strong> ({{ periodeLabel }})
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <!-- Filtre Agent -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Agent du parking</label>
                        <select
                            v-model="agentId"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30 bg-white"
                        >
                            <option value="">Tous les agents</option>
                            <option v-for="a in agents" :key="a.id" :value="String(a.id)">
                                {{ a.name }} {{ a.first_name ?? '' }} ({{ a.unique_code ?? 'Agent' }})
                            </option>
                        </select>
                    </div>

                    <!-- Période rapide -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Période rapide</label>
                        <select
                            v-model="periode"
                            @change="() => { dateFrom = ''; dateTo = ''; }"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30 bg-white"
                        >
                            <option value="jour">Aujourd'hui</option>
                            <option value="mois">Ce mois</option>
                            <option value="annee">Cette année</option>
                        </select>
                    </div>

                    <!-- Intervalle personnalisé (Du) -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Du</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                        />
                    </div>

                    <!-- Intervalle personnalisé (Au) -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Au</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                        />
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex items-center gap-2">
                        <PrimaryButton
                            type="button"
                            @click="applyFilter"
                            class="flex-1 justify-center py-2.5"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Filtrer
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            @click="resetFilter"
                            class="py-2.5 px-3"
                            title="Réinitialiser"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Exports -->
                <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Exporter le rapport :</span>
                    <a :href="buildExportUrl('pdf')"
                        class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 text-xs font-bold rounded-xl hover:bg-red-100 transition-colors border border-red-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Format PDF
                    </a>
                    <a :href="buildExportUrl('csv')"
                        class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 text-xs font-bold rounded-xl hover:bg-green-100 transition-colors border border-green-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Format Excel (CSV)
                    </a>
                </div>
            </div>

            <!-- ── Résumé des indicateurs ──────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <!-- Entrées -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Entrées enregistrées</p>
                    <p class="text-2xl font-black text-blue-600">{{ totalEntries }}</p>
                    <p class="text-xs text-gray-400">Stationnements créés</p>
                </div>

                <!-- Sorties -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Sorties effectuées</p>
                    <p class="text-2xl font-black text-indigo-600">{{ totalExits }}</p>
                    <p class="text-xs text-gray-400">Véhicules libérés</p>
                </div>

                <!-- Montant total -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Montant total généré</p>
                    <p class="text-xl font-black text-gray-900">{{ fmt(totalMontant) }}</p>
                    <p class="text-xs text-gray-400">Total sorties</p>
                </div>

                <!-- Reversé -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Montant Reversé</p>
                    <p class="text-xl font-black text-emerald-600">{{ fmt(totalReverse) }}</p>
                    <p class="text-xs text-gray-400">Validé en versement</p>
                </div>

                <!-- Non reversé -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Non reversé (Reste)</p>
                    <p class="text-xl font-black text-rose-600">{{ fmt(totalNonReverse) }}</p>
                    <p class="text-xs text-gray-400">À reverser</p>
                </div>
            </div>

            <!-- ── Tableau détaillé ────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-black text-gray-800">Historique des opérations</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ agentLabel }} — {{ periodeLabel }} ({{ sessions.length }} session{{ sessions.length > 1 ? 's' : '' }})</p>
                    </div>
                </div>

                <div v-if="sessions.length === 0" class="text-center py-16">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400 font-semibold">Aucune session trouvée pour les critères sélectionnés.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/75 text-xs text-gray-500 uppercase tracking-wider font-bold border-b border-gray-100">
                                <th class="px-5 py-3.5 text-left">Plaque</th>
                                <th class="px-5 py-3.5 text-left">Véhicule</th>
                                <th class="px-5 py-3.5 text-left">Parking</th>
                                <th class="px-5 py-3.5 text-left">Agent Entrée</th>
                                <th class="px-5 py-3.5 text-left">Sortie Par</th>
                                <th class="px-5 py-3.5 text-left">Entrée</th>
                                <th class="px-5 py-3.5 text-left">Sortie</th>
                                <th class="px-5 py-3.5 text-left">Durée</th>
                                <th class="px-5 py-3.5 text-right">Montant</th>
                                <th class="px-5 py-3.5 text-center">Statut / Reversé</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="s in sessions" :key="s.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5 font-mono font-bold text-gray-900">{{ s.license_plate }}</td>
                                <td class="px-5 py-3.5 text-gray-600">{{ [s.marque, s.modele].filter(Boolean).join(' ') || '—' }}</td>
                                <td class="px-5 py-3.5 text-gray-600">{{ s.parking }}</td>
                                <td class="px-5 py-3.5 text-gray-700 font-semibold">{{ s.agent_entry }}</td>
                                <td class="px-5 py-3.5 text-gray-700 font-semibold">{{ s.agent_exit }}</td>
                                <td class="px-5 py-3.5 text-gray-600 text-xs">{{ s.started_at }}</td>
                                <td class="px-5 py-3.5 text-gray-600 text-xs">{{ s.ended_at || 'En cours' }}</td>
                                <td class="px-5 py-3.5 text-gray-600 text-xs">{{ s.duration_minutes ? s.duration_minutes + ' min' : '—' }}</td>
                                <td class="px-5 py-3.5 text-right font-bold text-gray-900">
                                    {{ fmt(s.amount) }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span v-if="s.status === 'occupied'" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                                        En cours
                                    </span>
                                    <span v-else-if="s.versement_id" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
                                        Reversé ✓
                                    </span>
                                    <span v-else class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800">
                                        Non reversé
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-black text-gray-800 border-t border-gray-200">
                                <td colspan="8" class="px-5 py-3.5 text-right text-xs uppercase tracking-wider text-gray-500">Total généré</td>
                                <td class="px-5 py-3.5 text-right text-base text-gray-900">{{ fmt(totalMontant) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
