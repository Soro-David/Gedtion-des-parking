<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    dette:             { type: Number, default: 0 },
    montantJour:       { type: Number, default: 0 },
    montantMois:       { type: Number, default: 0 },
    montantAnnee:      { type: Number, default: 0 },
    nbJour:            { type: Number, default: 0 },
    nbMois:            { type: Number, default: 0 },
    nbAnnee:           { type: Number, default: 0 },
    dernierVersements: { type: Array, default: () => [] },
    derniersSessions:  { type: Array, default: () => [] },
});

const filtre = ref('jour');

const montantFiltre = computed(() => {
    if (filtre.value === 'mois') return props.montantMois;
    if (filtre.value === 'annee') return props.montantAnnee;
    return props.montantJour;
});

const nbFiltre = computed(() => {
    if (filtre.value === 'mois') return props.nbMois;
    if (filtre.value === 'annee') return props.nbAnnee;
    return props.nbJour;
});

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const fmtDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Tableau de bord Caissier" />

    <AuthenticatedLayout>
        <template #header>Tableau de bord</template>

        <div class="space-y-8">

            <!-- ── Dette + Montants encaissés (même ligne) ─────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-stretch">

                <!-- Card dette -->
                <div :class="['rounded-2xl border shadow-sm p-6 flex flex-col justify-between', dette > 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200']">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest" :class="dette > 0 ? 'text-red-500' : 'text-green-500'">Montant non reversé (dette)</p>
                            <p class="text-xs mt-0.5" :class="dette > 0 ? 'text-red-400' : 'text-green-400'">Sorties − versements reçus</p>
                        </div>
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', dette > 0 ? 'bg-red-100' : 'bg-green-100']">
                            <svg v-if="dette > 0" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <svg v-else class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <p :class="['text-3xl font-black', dette > 0 ? 'text-red-600' : 'text-green-600']">{{ fmt(dette) }}</p>
                    <p v-if="dette <= 0" class="mt-2 text-xs font-semibold text-green-600">Aucune dette en cours ✓</p>
                </div>

                <!-- Card montants encaissés -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Montants encaissés</p>
                        <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                            <button @click="filtre = 'jour'"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', filtre === 'jour' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                                Jour
                            </button>
                            <button @click="filtre = 'mois'"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', filtre === 'mois' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                                Mois
                            </button>
                            <button @click="filtre = 'annee'"
                                :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', filtre === 'annee' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                                Année
                            </button>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-gray-900">{{ fmt(montantFiltre) }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ nbFiltre }} stationnement{{ nbFiltre > 1 ? 's' : '' }}</p>
                </div>

            </div>

            <!-- ── Raccourcis ────────────────────────────────────────── -->
            <div>
                <h2 class="text-base font-black text-gray-500 uppercase tracking-widest mb-4">Actions rapides</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Link :href="route('caissier.parking-sessions.index')"
                        class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-blue-200 hover:shadow-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm4 4v8m0-8h3a2 2 0 010 4H9"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-700 text-center">Stationnements</span>
                    </Link>
                    <Link :href="route('caissier.checkout')"
                        class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-green-200 hover:shadow-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-green-50 group-hover:bg-green-100 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-700 text-center">Enregistrer sortie</span>
                    </Link>
                    <Link :href="route('caissier.reversements.index')"
                        class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-purple-200 hover:shadow-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-700 text-center">Versements</span>
                    </Link>
                    <Link :href="route('caissier.rapport.index')"
                        class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-orange-200 hover:shadow-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 group-hover:bg-orange-100 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-700 text-center">Rapport</span>
                    </Link>
                </div>
            </div>

            <!-- ── Historique des sorties ─────────────────────────── -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-black text-gray-500 uppercase tracking-widest">Dernières sorties encaissées</h2>
                    <Link :href="route('caissier.history')"
                        class="text-xs text-blue-600 font-bold hover:underline">
                        Voir tout →
                    </Link>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <template v-if="derniersSessions.length">
                        <div v-for="s in derniersSessions" :key="s.id"
                            class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                            <div>
                                <p class="text-sm font-bold text-gray-800 uppercase tracking-widest">{{ s.license_plate }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ [s.marque, s.modele].filter(Boolean).join(' ') || '—' }}
                                    <span v-if="s.parking_name"> · {{ s.parking_name }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-green-700">{{ fmt(s.amount) }}</p>
                                <p class="text-xs text-gray-400">{{ fmtDate(s.ended_at) }}</p>
                            </div>
                        </div>
                    </template>
                    <div v-else class="px-5 py-10 text-center text-gray-400 text-sm">
                        Aucune sortie encaissée pour le moment.
                    </div>
                </div>
            </div>

            <!-- ── Derniers versements ────────────────────────────── -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-black text-gray-500 uppercase tracking-widest">Derniers versements reçus</h2>
                    <Link :href="route('caissier.versements.index')"
                        class="text-xs text-blue-600 font-bold hover:underline">
                        Voir tout →
                    </Link>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <template v-if="dernierVersements.length">
                        <div v-for="v in dernierVersements" :key="v.id"
                            class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ fmt(v.paid_amount) }}</p>
                                <p class="text-xs text-gray-400">{{ fmtDate(v.created_at) }} — par {{ v.admin_name }}</p>
                            </div>
                            <span :class="['text-xs font-bold px-3 py-1 rounded-full',
                                v.remaining_debt > 0 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700']">
                                {{ v.remaining_debt > 0 ? 'Reste ' + fmt(v.remaining_debt) : 'Soldé' }}
                            </span>
                        </div>
                    </template>
                    <div v-else class="px-5 py-10 text-center text-gray-400 text-sm">
                        Aucun versement reçu pour le moment.
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

