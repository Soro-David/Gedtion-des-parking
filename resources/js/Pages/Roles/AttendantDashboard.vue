<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ }),
    },
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
    <div class="space-y-8">

        <!-- ── Dette ──────────────────────────────────────────── -->
        <div v-if="stats.dette > 0" class="flex items-center gap-4 bg-red-50 border border-red-200 rounded-2xl px-6 py-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-red-700">Montant non reversé (dette)</p>
                <p class="text-2xl font-black text-red-600 mt-0.5">{{ fmt(stats.dette) }}</p>
            </div>
            <Link :href="route('attendant.reversements.index')"
                class="px-4 py-2 bg-red-600 text-white text-sm font-bold rounded-xl hover:bg-red-700 transition-colors shrink-0">
                Reverser
            </Link>
        </div>
        <div v-else class="flex items-center gap-4 bg-green-50 border border-green-200 rounded-2xl px-6 py-4">
            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-green-700">Aucune dette en cours</p>
                <p class="text-xs text-green-600 mt-0.5">Tous vos montants ont été reversés.</p>
            </div>
        </div>

        <!-- ── Montants encaissés ──────────────────────────────── -->
        <div>
            <h2 class="text-base font-black text-gray-500 uppercase tracking-widest mb-4">Montants encaissés</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-1">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Aujourd'hui</p>
                    <p class="text-2xl font-black text-gray-900">{{ fmt(stats.montantJour) }}</p>
                    <p class="text-sm text-gray-500">{{ stats.nbJour ?? 0 }} sortie{{ (stats.nbJour ?? 0) > 1 ? 's' : '' }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-1">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Ce mois</p>
                    <p class="text-2xl font-black text-gray-900">{{ fmt(stats.montantMois) }}</p>
                    <p class="text-sm text-gray-500">{{ stats.nbMois ?? 0 }} sortie{{ (stats.nbMois ?? 0) > 1 ? 's' : '' }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-1">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Cette année</p>
                    <p class="text-2xl font-black text-gray-900">{{ fmt(stats.montantAnnee) }}</p>
                    <p class="text-sm text-gray-500">{{ stats.nbAnnee ?? 0 }} sortie{{ (stats.nbAnnee ?? 0) > 1 ? 's' : '' }}</p>
                </div>
            </div>
        </div>

        <!-- ── Actions rapides ─────────────────────────────────── -->
        <div>
            <h2 class="text-base font-black text-gray-500 uppercase tracking-widest mb-4">Actions rapides</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <Link :href="route('attendant.parking-sessions.index')"
                    class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-blue-200 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm4 4v8m0-8h3a2 2 0 010 4H9"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 text-center">Stationnements</span>
                </Link>
                <Link :href="route('attendant.checkout')"
                    class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-green-200 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-green-50 group-hover:bg-green-100 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 text-center">Enregistrer sortie</span>
                </Link>
                <Link :href="route('attendant.reversements.index')"
                    class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-purple-200 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 text-center">Reversements</span>
                </Link>
                <Link :href="route('attendant.history')"
                    class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-orange-200 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 group-hover:bg-orange-100 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 text-center">Historique</span>
                </Link>
            </div>
        </div>

        <!-- ── Dernières sorties enregistrées ─────────────────── -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-black text-gray-500 uppercase tracking-widest">Dernières sorties enregistrées</h2>
                <Link :href="route('attendant.history')"
                    class="text-xs text-blue-600 font-bold hover:underline">
                    Voir tout →
                </Link>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <template v-if="stats.dernieresSessions && stats.dernieresSessions.length">
                    <div v-for="s in stats.dernieresSessions" :key="s.id"
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
                    Aucune sortie enregistrée pour le moment.
                </div>
            </div>
        </div>

    </div>
</template>
