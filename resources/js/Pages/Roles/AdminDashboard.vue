<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ supervisors: 0, attendants: 0, revenue: '0 FCFA' }),
    },
});

const cards = [
    {
        name: 'Superviseurs',
        key: 'supervisors',
        icon: 'users',
        color: 'from-violet-500 to-violet-700',
        bg: 'bg-violet-50',
        text: 'text-violet-700',
        route: 'admin.superviseur.index',
        label: 'Voir la liste',
    },
    {
        name: 'Agents (Attendants)',
        key: 'attendants',
        icon: 'shield',
        color: 'from-indigo-500 to-indigo-700',
        bg: 'bg-indigo-50',
        text: 'text-indigo-700',
        route: null,
        label: null,
    },
    {
        name: 'Revenus Journaliers',
        key: 'revenue',
        icon: 'credit-card',
        color: 'from-amber-500 to-orange-600',
        bg: 'bg-amber-50',
        text: 'text-amber-700',
        route: null,
        label: null,
    },
];

const getIcon = (name) => {
    const icons = {
        'users': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'shield': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'truck': 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2',
        'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    };
    return icons[name] || '';
};
</script>

<template>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div
            v-for="card in cards"
            :key="card.name"
            class="relative bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 overflow-hidden group"
        >
            <!-- Background gradient accent -->
            <div :class="`absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-5 transition-opacity duration-300 ${card.color}`"></div>

            <div class="flex items-start justify-between mb-4">
                <div :class="`p-3 rounded-xl ${card.bg}`">
                    <svg class="w-6 h-6" :class="card.text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(card.icon)" />
                    </svg>
                </div>
                <Link
                    v-if="card.route"
                    :href="route(card.route)"
                    class="text-xs font-semibold text-gray-400 hover:text-gray-700 transition-colors flex items-center gap-1"
                >
                    {{ card.label }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
            </div>

            <p class="text-sm text-gray-500 font-medium">{{ card.name }}</p>
            <p class="text-3xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ stats[card.key] ?? '—' }}</p>
        </div>
    </div>

    <!-- Bottom panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-5">Actions Rapides</h3>
            <div class="space-y-3">
                <Link
                    :href="route('admin.superviseur.create')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-[#4A1725]/5 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-[#4A1725]/10 text-[#4A1725] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 group-hover:text-[#4A1725] transition-colors">Ajouter un Superviseur</p>
                        <p class="text-xs text-gray-400">Créer un nouveau compte superviseur</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 ml-auto group-hover:text-[#4A1725] transition-colors" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
                <Link
                    :href="route('admin.superviseur.index')"
                    class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-[#4A1725]/5 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 group-hover:text-[#4A1725] transition-colors">Gérer les Superviseurs</p>
                        <p class="text-xs text-gray-400">Voir et gérer tous les superviseurs</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 ml-auto group-hover:text-[#4A1725] transition-colors" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </Link>
            </div>
        </div>

        <!-- Hierarchy Summary -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-5">Hiérarchie du Système</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-[#4A1725]/5">
                    <div class="w-8 h-8 rounded-lg bg-[#4A1725] text-white flex items-center justify-center text-xs font-bold">SA</div>
                    <div>
                        <!-- <p class="text-sm font-bold text-gray-900">Super Admin</p> -->
                        <p class="text-xs text-gray-400">Gère les Superviseurs</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-[#4A1725] bg-[#4A1725]/10 px-2 py-1 rounded-full">1</span>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-violet-50">
                    <div class="w-8 h-8 rounded-lg bg-violet-500 text-white flex items-center justify-center text-xs font-bold">SV</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Superviseur</p>
                        <p class="text-xs text-gray-400">Gère les Agents</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-violet-700 bg-violet-100 px-2 py-1 rounded-full">{{ stats.supervisors ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500 text-white flex items-center justify-center text-xs font-bold">AG</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Agent (Attendant)</p>
                        <p class="text-xs text-gray-400">Gère les Sessions de Parking</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-indigo-700 bg-indigo-100 px-2 py-1 rounded-full">{{ stats.attendants ?? 0 }}</span>
                </div>
                <!-- <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs font-bold">DR</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Conducteur</p>
                        <p class="text-xs text-gray-400">Utilise les parkings</p>
                    </div>
                    <span class="ml-auto text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-full">{{ stats.drivers ?? 0 }}</span>
                </div> -->
            </div>
        </div>
    </div>
</template>
