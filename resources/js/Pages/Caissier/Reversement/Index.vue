<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    reversements: { type: Array, default: () => [] },
});

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const totalPercu = computed(() => props.reversements.reduce((s, r) => s + (r.montant_percu ?? r.amount), 0));
const totalVerse = computed(() => props.reversements.filter(r => r.status === 'confirmed').reduce((s, r) => s + r.amount, 0));
const totalReste = computed(() => props.reversements.reduce((s, r) => s + (r.reste ?? 0), 0));
</script>

<template>
    <Head title="Historique des versements" />
    <AuthenticatedLayout>
        <template #header>Historique des versements</template>

        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Cartes résumé -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total perçu</p>
                        <p class="text-lg font-black text-blue-700">{{ fmt(totalPercu) }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ reversements.length }} versement(s)</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total versé</p>
                        <p class="text-lg font-black text-emerald-700">{{ fmt(totalVerse) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
                    <div :class="['w-11 h-11 rounded-xl flex items-center justify-center shrink-0', totalReste > 0 ? 'bg-amber-100' : 'bg-gray-100']">
                        <svg :class="['w-5 h-5', totalReste > 0 ? 'text-amber-600' : 'text-gray-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reste à confirmer</p>
                        <p :class="['text-lg font-black', totalReste > 0 ? 'text-amber-700' : 'text-gray-400']">{{ fmt(totalReste) }}</p>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div v-if="reversements.length > 0" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="font-black text-slate-800 text-base">Liste des versements</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-xs text-slate-500 uppercase tracking-widest font-bold">
                                <th class="px-5 py-3 text-left">Date de versement</th>
                                <th class="px-5 py-3 text-left">Destinataire</th>
                                <th class="px-5 py-3 text-right">Montant perçu</th>
                                <th class="px-5 py-3 text-right">Montant versé</th>
                                <th class="px-5 py-3 text-right">Reste</th>
                                <th class="px-5 py-3 text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="r in reversements" :key="r.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-800">{{ formatDate(r.created_at) }}</p>
                                    <p v-if="r.confirmed_at" class="text-xs text-emerald-600 mt-0.5">Confirmé le {{ formatDate(r.confirmed_at) }}</p>
                                    <p v-if="r.note" class="text-xs text-slate-400 mt-0.5 truncate max-w-[180px]">{{ r.note }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-slate-700">{{ r.receiver }}</span>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ r.sessions_count }} session(s)</p>
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-slate-800">{{ fmt(r.montant_percu ?? r.amount) }}</td>
                                <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ fmt(r.amount) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <span :class="['font-bold', r.reste > 0 ? 'text-amber-600' : 'text-slate-400']">{{ fmt(r.reste) }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wide', r.status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                        {{ r.status === 'confirmed' ? 'Confirmé' : 'En attente' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 font-black text-slate-700 text-sm border-t border-slate-200">
                                <td colspan="2" class="px-5 py-3 text-right text-xs uppercase tracking-widest text-slate-400">Total</td>
                                <td class="px-5 py-3 text-right text-blue-700">{{ fmt(totalPercu) }}</td>
                                <td class="px-5 py-3 text-right text-emerald-700">{{ fmt(totalVerse) }}</td>
                                <td class="px-5 py-3 text-right" :class="totalReste > 0 ? 'text-amber-600' : 'text-slate-400'">{{ fmt(totalReste) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Aucun versement -->
            <div v-else class="bg-slate-50 rounded-2xl border border-slate-100 px-6 py-14 text-center">
                <svg class="w-12 h-12 text-slate-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-slate-400 text-sm font-semibold">Aucun versement enregistré pour le moment.</p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
