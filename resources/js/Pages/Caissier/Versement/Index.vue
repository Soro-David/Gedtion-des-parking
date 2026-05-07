<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    versements: { type: Array, default: () => [] },
});

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const totalEncaisse = computed(() => props.versements.reduce((s, v) => s + (v.collected_amount ?? 0), 0));
const totalVerse    = computed(() => props.versements.reduce((s, v) => s + (v.paid_amount ?? 0), 0));
const totalDette    = computed(() => props.versements.length ? (props.versements[0].remaining_debt ?? 0) : 0);
</script>

<template>
    <Head title="Mes versements" />
    <AuthenticatedLayout>
        <template #header>Mes versements</template>

        <div class="max-w-5xl mx-auto space-y-6">

            <!-- ── Cartes résumé ────────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Total encaissé -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total encaissé</p>
                        <p class="text-lg font-black text-blue-700">{{ fmt(totalEncaisse) }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ versements.length }} versement(s)</p>
                    </div>
                </div>

                <!-- Total versé -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total versé</p>
                        <p class="text-lg font-black text-emerald-700">{{ fmt(totalVerse) }}</p>
                    </div>
                </div>

                <!-- Dette restante -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
                    <div :class="['w-11 h-11 rounded-xl flex items-center justify-center shrink-0',
                        totalDette > 0 ? 'bg-amber-100' : 'bg-gray-100']">
                        <svg :class="['w-5 h-5', totalDette > 0 ? 'text-amber-600' : 'text-gray-400']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dette restante</p>
                        <p :class="['text-lg font-black', totalDette > 0 ? 'text-amber-600' : 'text-gray-400']">
                            {{ fmt(totalDette) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Tableau des versements ───────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50">
                    <h2 class="font-black text-gray-700">Historique des versements</h2>
                </div>

                <div v-if="versements.length === 0" class="text-center py-16">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400 font-semibold">Aucun versement enregistré.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest font-bold">
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-right">Encaissé</th>
                                <th class="px-5 py-3 text-right">Dette préc.</th>
                                <th class="px-5 py-3 text-right">Total dû</th>
                                <th class="px-5 py-3 text-right">Versé</th>
                                <th class="px-5 py-3 text-right">Reste</th>
                                <th class="px-5 py-3 text-left">Reçu par</th>
                                <th class="px-5 py-3 text-left">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="v in versements" :key="v.id"
                                class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ formatDate(v.created_at) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-gray-900">{{ fmt(v.collected_amount) }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ fmt(v.previous_debt) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-gray-900">{{ fmt(v.total_due) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-emerald-700">{{ fmt(v.paid_amount) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span :class="[
                                        'font-bold text-xs px-2.5 py-1 rounded-full',
                                        v.remaining_debt > 0 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'
                                    ]">
                                        {{ fmt(v.remaining_debt) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ v.admin_name }}</td>
                                <td class="px-5 py-3 text-gray-400 italic">{{ v.note || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
