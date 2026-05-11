<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import SidebarLink from './SidebarLink.vue';
import {
    ROLE_SUPERADMIN,
    ROLE_ADMIN,
    ROLE_SUPERVISOR,
    ROLE_ATTENDANT,
    ROLE_DRIVER,
    ROLE_CAISSIER,
} from '@/Constants/Roles';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['toggle']);

const page = usePage();
// Be defensive: page.props or auth may not exist (e.g., unauthenticated requests)
const user = computed(() => page.props?.auth?.user ?? null);
const role = computed(() => user.value?.role ?? '');

const menuItems = computed(() => {
    const common = [{ name: 'Dashboard', route: 'dashboard', icon: 'dashboard' }];

    // If no user (not authenticated), only show common links
    if (!user.value) return common;

    switch (role.value) {
        case ROLE_SUPERADMIN:
            return [
                { name: 'Tableau de bord', route: 'superadmin.dashboard', icon: 'dashboard' },
                { name: 'Historique Entrées/Sorties', route: 'superadmin.history.index', icon: 'clock' },
                { name: 'Parkings', route: 'superadmin.parkings.index', icon: 'map-pin' },
                { name: 'Gestion des Admins', route: 'superadmin.admins.index', icon: 'users' },
            ];

        case ROLE_ADMIN:
            return [
                ...common,
                { name: 'Parkings', route: 'parkings.index', icon: 'map-pin' },
                { name: 'Tarifs de stationnement', route: 'parking-rates.index', icon: 'tag' },
                { name: 'Gestion des Utilisateurs', route: 'admin.users.index', icon: 'users' },
                { name: 'Historique Entrées/Sorties', route: 'admin.history.index', icon: 'clock' },
                { name: 'Faire un versement', route: 'admin.versements.index', icon: 'banknote' },
            ];

        case ROLE_SUPERVISOR:
            return [
                ...common,
                { name: 'Mes Parkings', route: 'parkings.index', icon: 'map-pin' },
                { name: 'Tarifs de stationnement', route: 'parking-rates.index', icon: 'tag' },
                { name: 'Gestion des Utilisateurs', route: 'supervisor.users.index', icon: 'users' },
                { name: 'Historique Entrées/Sorties', route: 'supervisor.history.index', icon: 'clock' },
                // { name: 'Reversements reçus', route: 'supervisor.reversements.index', icon: 'arrow-down-circle' },
                { name: 'Faire un versement', route: 'supervisor.versements.index', icon: 'banknote' },
            ];

        case ROLE_ATTENDANT:
            return [
                ...common,
                // { name: 'Gestion Conducteurs', route: 'attendant.drivers.index', icon: 'users' },
                { name: 'Stationnements', route: 'attendant.parking-sessions.index', icon: 'parking' },
                { name: 'Enregistrer sortie', route: 'attendant.checkout', icon: 'log-out' },
                // { name: 'Scanner QR', route: 'dashboard', icon: 'maximize' },
                { name: 'Historique sortie', route: 'attendant.history', icon: 'clock' },
                { name: 'Historique versements', route: 'attendant.reversements.index', icon: 'banknote' },
            ];

        case ROLE_CAISSIER:
            return [
                ...common,
                { name: 'Stationnements', route: 'caissier.parking-sessions.index', icon: 'parking' },
                { name: 'Enregistrer sortie', route: 'caissier.checkout', icon: 'log-out' },
                { name: 'Historique caisse', route: 'caissier.history', icon: 'clock' },
                { name: 'Historique versements', route: 'caissier.versements.index', icon: 'banknote' },
                { name: 'Rapport', route: 'caissier.rapport.index', icon: 'bar-chart' },
            ];

        case ROLE_DRIVER:
            return [
                ...common,
                { name: 'Trouver un Parking', route: 'dashboard', icon: 'search' },
                { name: 'Mes Véhicules', route: 'dashboard', icon: 'truck' },
                { name: 'Mes Réservations', route: 'dashboard', icon: 'calendar' },
                { name: 'Mon Solde', route: 'dashboard', icon: 'wallet' },
            ];

        default:
            return common;
    }
});

// Safe check for route current - some route names may not exist or Ziggy may be unset
const isRouteCurrent = (routeName) => {
    try {
        return route().current(routeName);
    } catch (e) {
        return false;
    }
};

// Safe route generator - returns '#' if the route does not exist
const safeRoute = (name) => {
    try {
        return route(name);
    } catch (e) {
        return '#';
    }
};

const logout = () => {
    router.post(route('logout'));
};

const getIcon = (name) => {
    const icons = {
        'dashboard': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'users': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        'map-pin': 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        'bar-chart': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'maximize': 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
        'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'search': 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        'truck': 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2m-6-1a1 1 0 011-1h2',
        'calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'wallet': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'parking': 'M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm4 4v8m0-8h3a2 2 0 010 4H9',
        'tag': 'M7 7h.01M7 3H5a2 2 0 00-2 2v2c0 .526.213 1.026.586 1.4l7 7a2 2 0 002.828 0l5-5a2 2 0 000-2.828l-7-7A2 2 0 009 3H7z',
        'log-out': 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
        'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'arrow-down-circle': 'M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z',
        'banknote': 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'shield': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    };
    return icons[name] || icons['dashboard'];
};
</script>

<template>
    <aside
        :class="[
            'fixed left-0 top-0 z-40 h-screen flex flex-col transition-all glass-dark duration-300 ease-in-out',
            isOpen ? 'w-64 translate-x-0' : '-translate-x-full sm:translate-x-0',
            isCollapsed && !isOpen ? 'sm:w-20' : 'sm:w-64',
        ]"
    >
        <div class="flex flex-col h-full px-3 py-4 overflow-y-auto">

            <!-- ── Logo ─────────────────────────────────────────── -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex flex-1 items-center justify-center">
                    <!-- Logo complet (sidebar ouverte) -->
                    <div
                        v-show="!isCollapsed || isOpen"
                        class="bg-white w-full flex items-center justify-center shadow-md overflow-hidden rounded-xl border border-white/10"
                    >
                        <img
                            src="/Logo_Plateau.svg"
                            alt="Plateau Smart City"
                            class="w-full h-16 object-contain p-1 transition-all duration-300"
                        />
                    </div>
                    <!-- Icône réduite -->
                    <div
                        v-show="isCollapsed && !isOpen"
                        class="bg-white h-11 w-11 rounded-xl overflow-hidden shadow-lg flex-shrink-0 border border-white/20 flex items-center justify-center"
                    >
                        <img
                            src="/Logo_Plateau.svg"
                            alt="Plateau Smart City"
                            class="h-full w-full object-contain"
                        />
                    </div>
                </div>
                <!-- Fermer sur mobile -->
                <button
                    @click="$emit('toggle')"
                    class="ml-2 p-1 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-colors sm:hidden"
                    aria-label="Fermer le menu"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div><br><br>

            <!-- ── Label "Navigation" ──────────────────────────── -->
            <!-- <p
                v-show="!isCollapsed || isOpen"
                class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-widest text-gray-500 select-none"
            >
                Navigation
            </p> -->

            <!-- ── Menu ───────────────────────────────────────── -->
            <nav class="flex-1 space-y-1">
                <div v-for="item in menuItems" :key="item.name">
                    <SidebarLink
                        :href="safeRoute(item.route)"
                        :active="isRouteCurrent(item.route)"
                        :is-collapsed="isCollapsed && !isOpen"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 flex-shrink-0 transition duration-75" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(item.icon)" />
                            </svg>
                        </template>
                        {{ item.name }}
                    </SidebarLink>
                </div>
            </nav>

            <!-- ── Séparateur ─────────────────────────────────── -->
            <!-- <div class="my-4 border-t border-white/10"></div> -->

            <!-- ── Profil utilisateur ─────────────────────────── -->
            <!-- <div
                v-if="user"
                :class="[
                    'flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 transition-all duration-300',
                    isCollapsed && !isOpen ? 'justify-center p-2' : 'px-3 py-3',
                ]"
            >
                <div class="flex-shrink-0 flex h-9 w-9 items-center justify-center rounded-full bg-[#143f85] font-bold text-white text-sm uppercase border border-white/20 shadow">
                    {{ user.name ? user.name.charAt(0) : '?' }}
                </div>
                <div v-show="!isCollapsed || isOpen" class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ user.name || 'Utilisateur' }}</p>
                    <p class="capitalize text-xs text-gray-400">{{ role }}</p>
                </div>
            </div> -->

            <!-- ── Déconnexion ─────────────────────────────────── -->
            <!-- <button
                @click="logout"
                :class="[
                    'mt-3 flex items-center gap-3 w-full rounded-lg px-3 py-2.5 text-sm font-medium text-gray-400',
                    'hover:bg-red-500/10 hover:text-red-400 transition-all duration-200 group',
                    isCollapsed && !isOpen ? 'justify-center' : '',
                ]"
                aria-label="Déconnexion"
            >
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span v-show="!isCollapsed || isOpen" class="whitespace-nowrap">Déconnexion</span>
            </button> -->

        </div>
    </aside>
</template>

<style scoped>
/* Assure un défilement fluide si le contenu déborde */
aside {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
</style>