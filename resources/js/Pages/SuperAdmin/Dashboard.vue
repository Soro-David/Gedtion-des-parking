<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_admins: 0,
            total_parkings: 0,
            active_sessions: 0,
            today_sessions: 0,
            today_exits: 0,
            today_revenue: 0,
        }),
    },
});

function formatCFA(n) {
    if (!n && n !== 0) return '—';
    return Number(n).toLocaleString('fr-FR') + ' FCFA';
}
</script>

<template>
    <Head title="Tableau de Bord — Super Admin" />

    <AuthenticatedLayout>
        <template #header>Tableau de Bord</template>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

            <!-- Admins -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-xl bg-[#487119]/10">
                        <svg class="w-6 h-6 text-[#487119]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <Link :href="route('superadmin.admins.index')" class="text-xs font-semibold text-gray-400 hover:text-[#487119] transition-colors flex items-center gap-1">
                        Gérer
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </Link>
                </div>
                <p class="text-sm text-gray-500 font-medium">Admins mairie</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.total_admins }}</p>
            </div>

            <!-- Parkings -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-xl bg-emerald-50">
                        <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <Link :href="route('superadmin.parkings.index')" class="text-xs font-semibold text-gray-400 hover:text-emerald-700 transition-colors flex items-center gap-1">
                        Voir
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </Link>
                </div>
                <p class="text-sm text-gray-500 font-medium">Parkings gérés</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.total_parkings }}</p>
            </div>

            <!-- Sessions actives -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-xl bg-blue-50">
                        <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <Link :href="route('superadmin.history.index')" class="text-xs font-semibold text-gray-400 hover:text-blue-700 transition-colors flex items-center gap-1">
                        Voir
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </Link>
                </div>
                <p class="text-sm text-gray-500 font-medium">Véhicules actuellement garés</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.active_sessions }}</p>
            </div>

            <!-- Entrées aujourd'hui -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-xl bg-indigo-50">
                        <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 font-medium">Entrées aujourd'hui</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.today_sessions }}</p>
            </div>

            <!-- Sorties aujourd'hui -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-xl bg-orange-50">
                        <svg class="w-6 h-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 font-medium">Sorties aujourd'hui</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.today_exits }}</p>
            </div>

            <!-- Recettes aujourd'hui -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-xl bg-amber-50">
                        <svg class="w-6 h-6 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 font-medium">Recettes du jour</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ formatCFA(stats.today_revenue) }}</p>
            </div>
        </div>

        <!-- Quick Actions + Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-5">Actions Rapides</h3>
                <div class="space-y-3">

                    <Link :href="route('superadmin.admins.create')" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-[#487119]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-[#487119]/10 text-[#487119] flex items-center justify-center flex-shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <p class="font-semibold text-gray-800 group-hover:text-[#487119] transition-colors">Créer un compte Admin mairie</p>
                            <p class="text-xs text-gray-400">Inviter un nouvel administrateur</p>
                        </div>
                        <svg class="h-5 w-5 text-gray-300 group-hover:text-[#487119] flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>

                    <Link :href="route('superadmin.admins.index')" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-[#487119]/5 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-[#487119]/10 text-[#487119] flex items-center justify-center flex-shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <p class="font-semibold text-gray-800 group-hover:text-[#487119] transition-colors">Voir tous les Admins</p>
                            <p class="text-xs text-gray-400">Liste des administrateurs créés</p>
                        </div>
                        <svg class="h-5 w-5 text-gray-300 group-hover:text-[#487119] flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>

                    <Link :href="route('superadmin.history.index')" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-blue-50 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <p class="font-semibold text-gray-800 group-hover:text-blue-700 transition-colors">Historique Entrées / Sorties</p>
                            <p class="text-xs text-gray-400">Journal complet des passages</p>
                        </div>
                        <svg class="h-5 w-5 text-gray-300 group-hover:text-blue-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>

                    <Link :href="route('superadmin.parkings.index')" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-emerald-50 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <p class="font-semibold text-gray-800 group-hover:text-emerald-700 transition-colors">Voir les Parkings</p>
                            <p class="text-xs text-gray-400">Disponibilité et position des parkings</p>
                        </div>
                        <svg class="h-5 w-5 text-gray-300 group-hover:text-emerald-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Récapitulatif -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-5">Récapitulatif global</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-[#487119]/5">
                        <div class="w-8 h-8 rounded-lg bg-[#487119] text-white flex items-center justify-center text-xs font-bold">AD</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Admins</p>
                            <p class="text-xs text-gray-400">Mairie de la ville</p>
                        </div>
                        <span class="ml-auto text-xs font-bold text-[#487119] bg-[#487119]/10 px-2 py-1 rounded-full">{{ stats.total_admins }}</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">PK</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Parkings</p>
                            <p class="text-xs text-gray-400">Zones de stationnement</p>
                        </div>
                        <span class="ml-auto text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-full">{{ stats.total_parkings }}</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-blue-50">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs font-bold">EN</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Véhicules garés</p>
                            <p class="text-xs text-gray-400">Sessions actives en cours</p>
                        </div>
                        <span class="ml-auto text-xs font-bold text-blue-700 bg-blue-100 px-2 py-1 rounded-full">{{ stats.active_sessions }}</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50">
                        <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs font-bold">₣</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Recettes du jour</p>
                            <p class="text-xs text-gray-400">Total des paiements encaissés</p>
                        </div>
                        <span class="ml-auto text-xs font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded-full">{{ formatCFA(stats.today_revenue) }}</span>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
