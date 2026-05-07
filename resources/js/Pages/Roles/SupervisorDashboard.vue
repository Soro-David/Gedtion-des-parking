<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ attendants: 0, caissiers: 0, parkings: 0 }),
    },
});
</script>

<template>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <!-- Mes Agents -->
        <div class="relative bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-indigo-50">
                    <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <Link :href="route('supervisor.attendants.index')" class="text-xs font-semibold text-gray-400 hover:text-indigo-700 transition-colors flex items-center gap-1">
                    Gérer
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </Link>
            </div>
            <p class="text-sm text-gray-500 font-medium">Mes Agents </p>
            <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.attendants ?? 0 }}</p>
        </div>

        <!-- Caissiers -->
        <div class="relative bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-amber-50">
                    <svg class="w-6 h-6 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <Link :href="route('supervisor.caissiers.index')" class="text-xs font-semibold text-gray-400 hover:text-amber-700 transition-colors flex items-center gap-1">
                    Gérer
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </Link>
            </div>
            <p class="text-sm text-gray-500 font-medium">Caissiers</p>
            <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.caissiers ?? 0 }}</p>
        </div>

        <!-- Parkings -->
        <div class="relative bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-emerald-50">
                    <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <Link :href="route('parkings.index')" class="text-xs font-semibold text-gray-400 hover:text-emerald-700 transition-colors flex items-center gap-1">
                    Voir
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </Link>
            </div>
            <p class="text-sm text-gray-500 font-medium">Parkings</p>
            <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats.parkings ?? 0 }}</p>
        </div>
    </div>

    <!-- Content panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-5">Actions Rapides</h3>
            <div class="space-y-3">
                <Link
                    :href="route('supervisor.attendants.create')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-indigo-50 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 group-hover:text-indigo-700 transition-colors">Ajouter un Agent</p>
                        <p class="text-xs text-gray-400">Créer un nouveau compte agent</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-indigo-500 transition-colors flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
                <Link
                    :href="route('supervisor.attendants.index')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-indigo-50 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 group-hover:text-indigo-700 transition-colors">Voir tous mes Agents</p>
                        <p class="text-xs text-gray-400">Liste complète des agents sous votre supervision</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-indigo-500 transition-colors flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
                <Link
                    :href="route('supervisor.caissiers.create')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-amber-50 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 group-hover:text-amber-700 transition-colors">Ajouter un Caissier</p>
                        <p class="text-xs text-gray-400">Créer un nouveau compte caissier</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-amber-500 transition-colors flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
                <Link
                    :href="route('supervisor.caissiers.index')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-amber-50 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 group-hover:text-amber-700 transition-colors">Voir tous les Caissiers</p>
                        <p class="text-xs text-gray-400">Liste complète des caissiers</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-amber-500 transition-colors flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
                <Link
                    :href="route('parkings.index')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-emerald-50 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 group-hover:text-emerald-700 transition-colors">Gérer les Parkings</p>
                        <p class="text-xs text-gray-400">Voir et gérer tous les parkings</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-emerald-500 transition-colors flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
            </div>
        </div>

        <!-- Résumé -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-5">Récapitulatif</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500 text-white flex items-center justify-center text-xs font-bold">AG</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Agents</p>
                        <p class="text-xs text-gray-400">Sous votre supervision</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-indigo-700 bg-indigo-100 px-2 py-1 rounded-full">{{ stats.attendants ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50">
                    <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs font-bold">CA</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Caissiers</p>
                        <p class="text-xs text-gray-400">Gestion de la caisse</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded-full">{{ stats.caissiers ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs font-bold">PK</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Parkings</p>
                        <p class="text-xs text-gray-400">Zones de stationnement</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-full">{{ stats.parkings ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
