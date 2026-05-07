<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    sessions:        { type: Array,  default: () => [] },
    totalMontant:    { type: Number, default: 0 },
    totalSessions:   { type: Number, default: 0 },
    totalReverse:    { type: Number, default: 0 },
    totalNonReverse: { type: Number, default: 0 },
    filters:         { type: Object, default: () => ({ periode: 'jour', date_from: '', date_to: '' }) },
});

const periode  = ref(props.filters.periode ?? 'jour');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo   = ref(props.filters.date_to   ?? '');

function applyFilter() {
    const params = (dateFrom.value && dateTo.value)
        ? { date_from: dateFrom.value, date_to: dateTo.value }
        : { periode: periode.value };
    router.get(route('caissier.rapport.index'), params, { preserveState: false });
}

function resetFilter() {
    periode.value  = 'jour';
    dateFrom.value = '';
    dateTo.value   = '';
    router.get(route('caissier.rapport.index'), { periode: 'jour' }, { preserveState: false });
}

function buildExportUrl(type) {
    const base = type === 'pdf'
        ? route('caissier.rapport.export-pdf')
        : route('caissier.rapport.export-csv');
    const params = (dateFrom.value && dateTo.value)
        ? `?date_from=${dateFrom.value}&date_to=${dateTo.value}`
        : `?periode=${periode.value}`;
    return base + params;
}

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const periodeLabel = computed(() => {
    if (props.filters.date_from && props.filters.date_to)
        return `${props.filters.date_from} → ${props.filters.date_to}`;
    return { jour: "Aujourd'hui", mois: 'Ce mois', annee: 'Cette année' }[props.filters.periode] ?? '';
});
</script>

<template>
    <Head title="Rapport caissier" />

    <AuthenticatedLayout>
        <template #header>Rapport</template>

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- ── Filtres ─────────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-black text-gray-700">Filtres</h2>

                <div class="flex flex-wrap items-end gap-4 w-full">
                    <!-- Période rapide -->
                    <div class="flex flex-col gap-1 flex-1 min-w-[140px]">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Période</label>
                        <select
                            v-model="periode"
                            @change="() => { dateFrom = ''; dateTo = ''; }"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30 bg-white"
                        >
                            <option value="jour">Aujourd'hui</option>
                            <option value="mois">Ce mois</option>
                            <option value="annee">Cette année</option>
                        </select>
                    </div>

                    <!-- Séparateur -->
                    <span class="text-gray-300 font-bold self-center pb-0.5">ou</span>

                    <!-- Intervalle personnalisé -->
                    <div class="flex flex-col gap-1 flex-1 min-w-[140px]">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Du</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                        />
                    </div>
                    <div class="flex flex-col gap-1 flex-1 min-w-[140px]">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Au</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                        />
                    </div>

                    <!-- Boutons -->
                    <div class="flex items-end gap-2 pb-0.5">
                        <button
                            type="button"
                            @click="applyFilter"
                            class="flex items-center gap-2 px-5 py-2.5 bg-[#4A1725] text-white text-sm font-bold rounded-xl hover:bg-[#3a1020] transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Rechercher
                        </button>
                        <button
                            type="button"
                            @click="resetFilter"
                            class="flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Exports -->
                <div class="flex flex-wrap items-center gap-3 pt-1 border-t border-gray-50">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Exporter :</span>
                    <a :href="buildExportUrl('pdf')"
                        class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 text-sm font-bold rounded-xl hover:bg-red-100 transition-colors border border-red-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF
                    </a>
                    <a :href="buildExportUrl('csv')"
                        class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 text-sm font-bold rounded-xl hover:bg-green-100 transition-colors border border-green-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Excel
                    </a>
                </div>
            </div>

            <!-- ── Résumé ──────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest">Séances</p>
                    <p class="text-2xl font-black text-gray-900">{{ totalSessions }}</p>
                    <p class="text-xs text-gray-500">{{ periodeLabel }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest">Total encaissé</p>
                    <p class="text-xl font-black text-gray-900">{{ fmt(totalMontant) }}</p>
                    <p class="text-xs text-gray-500">{{ periodeLabel }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest">Reversé</p>
                    <p class="text-xl font-black text-green-700">{{ fmt(totalReverse) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-1">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest">Non reversé</p>
                    <p class="text-xl font-black text-red-600">{{ fmt(totalNonReverse) }}</p>
                </div>
            </div>

            <!-- ── Tableau ────────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50">
                    <h2 class="font-black text-gray-700">Détail des stationnements — {{ periodeLabel }}</h2>
                </div>

                <div v-if="sessions.length === 0" class="text-center py-16">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400 font-semibold">Aucun stationnement pour cette période.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest font-bold">
                                <th class="px-5 py-3 text-left">Plaque</th>
                                <th class="px-5 py-3 text-left">Véhicule</th>
                                <th class="px-5 py-3 text-left">Parking</th>
                                <th class="px-5 py-3 text-left">Entrée</th>
                                <th class="px-5 py-3 text-left">Sortie</th>
                                <th class="px-5 py-3 text-left">Durée</th>
                                <th class="px-5 py-3 text-right">Montant</th>
                                <th class="px-5 py-3 text-center">Reversé</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="s in sessions" :key="s.id"
                                class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3 font-mono font-bold text-gray-900">{{ s.license_plate }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ [s.marque, s.modele].filter(Boolean).join(' ') || '—' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ s.parking }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ s.started_at }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ s.ended_at }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ s.duration_minutes ? s.duration_minutes + ' min' : '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-gray-900">
                                    {{ new Intl.NumberFormat('fr-FR').format(s.amount) }} FCFA
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span :class="[
                                        'text-xs font-bold px-2.5 py-1 rounded-full',
                                        s.reversement_id ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                                    ]">
                                        {{ s.reversement_id ? 'Oui' : 'Non' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-black text-gray-800">
                                <td colspan="6" class="px-5 py-3 text-right text-xs uppercase tracking-widest text-gray-500">Total</td>
                                <td class="px-5 py-3 text-right">{{ fmt(totalMontant) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
