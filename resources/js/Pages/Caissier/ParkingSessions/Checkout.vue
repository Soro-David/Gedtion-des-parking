<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onUnmounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    plate:    { type: String, default: '' },
    sessions: { type: Array, default: () => [] },
    rates:    { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const search = ref(props.plate);
const doSearch = () => {
    router.get(route('caissier.checkout'), { plate: search.value }, { preserveState: true, replace: true });
};

// Filtre côté client en temps réel
const filtered = computed(() => {
    const q = search.value.trim().toUpperCase();
    if (!q) return props.sessions;
    return props.sessions.filter(s => s.license_plate.includes(q));
});

// Calcul JS du montant (miroir de ParkingRate::calculateAmount PHP)
const computeAmount = (minutes, parkingId) => {
    const m = Math.ceil(minutes);
    if (parkingId) {
        const match = props.rates
            .filter(r => r.parking_id === parkingId && r.from_minutes <= m && (r.to_minutes === null || r.to_minutes >= m))
            .sort((a, b) => b.from_minutes - a.from_minutes);
        if (match.length > 0) return match[0].amount;
    }
    const global = props.rates
        .filter(r => r.parking_id === null && r.from_minutes <= m && (r.to_minutes === null || r.to_minutes >= m))
        .sort((a, b) => b.from_minutes - a.from_minutes);
    return global.length > 0 ? global[0].amount : null;
};

// Modal + minuterie temps réel
const selected = ref(null);
const checkoutForm = useForm({ session_id: '' });
const liveMinutes = ref(0);
const liveAmount  = ref(null);
let timer = null;

const tickLive = () => {
    if (!selected.value) return;
    const mins = Math.ceil((Date.now() - new Date(selected.value.started_at).getTime()) / 60000);
    liveMinutes.value = mins;
    liveAmount.value  = computeAmount(mins, selected.value.parking_id);
};

const openModal = (session) => {
    selected.value = session;
    checkoutForm.session_id = session.id;
    tickLive();
    timer = setInterval(tickLive, 1000);
};
const closeModal = () => {
    selected.value = null;
    clearInterval(timer);
    timer = null;
};

const confirmCheckout = () => {
    checkoutForm.post(route('caissier.checkout.store'), {
        onSuccess: () => { closeModal(); },
    });
};

onUnmounted(() => clearInterval(timer));

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatDuration = (minutes) => {
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m ? `${h}h ${m}min` : `${h}h`;
};
</script>

<template>
    <Head title="Enregistrer une sortie" />

    <AuthenticatedLayout>
        <template #header>Enregistrement des sorties</template>

        <!-- Flash success -->
        <div v-if="flash.success" class="mb-6 px-5 py-3 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Enregistrer une sortie</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <span class="font-bold text-gray-700">{{ sessions.length }}</span> véhicule(s) actuellement stationné(s).
                    Cliquez sur une ligne pour enregistrer la sortie.
                </p>
            </div>
        </div>

        <!-- Barre de filtre -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 mb-6">
            <form @submit.prevent="doSearch" class="flex gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Filtrer par immatriculation…"
                        class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm uppercase font-semibold tracking-widest placeholder:normal-case placeholder:font-normal placeholder:tracking-normal focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none"
                    />
                </div>
                <PrimaryButton type="submit" class="px-5 py-2.5 rounded-xl whitespace-nowrap">
                    Rechercher
                </PrimaryButton>
                <button
                    v-if="search"
                    type="button"
                    @click="search = ''; doSearch()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-500 text-sm font-semibold rounded-xl transition-colors whitespace-nowrap"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Réinitialiser
                </button>
            </form>
        </div>

        <!-- Liste des sessions actives -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">
                    <span class="text-gray-900 font-bold">{{ filtered.length }}</span> résultat(s)
                    <span v-if="search" class="text-gray-500"> pour « <span class="uppercase tracking-widest text-gray-800">{{ search }}</span> »</span>
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Cliquez sur une ligne pour enregistrer la sortie
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Immatriculation</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Véhicule</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Parking</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Entrée</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Durée</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="(s, index) in filtered"
                            :key="s.id"
                            class="hover:bg-green-50/40 transition-colors group"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-semibold">{{ index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-gray-100 text-gray-800 border border-gray-200 uppercase tracking-widest shadow-sm">
                                    {{ s.license_plate }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <span v-if="s.marque || s.modele">{{ [s.marque, s.modele].filter(Boolean).join(' ') }}</span>
                                <span v-else class="text-gray-400 italic">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ s.parking_name || '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(s.started_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">{{ formatDuration(s.duration_minutes) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button
                                    @click="openModal(s)"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 text-sm font-semibold rounded-xl transition-all duration-200 active:scale-95"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Valider sortie
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0 && sessions.length > 0">
                            <td colspan="7" class="px-6 py-16 text-center text-gray-400 italic">Aucune session trouvée pour « {{ search }} ».</td>
                        </tr>
                        <tr v-if="sessions.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm4 4v8m0-8h3a2 2 0 010 4H9" />
                                    </svg>
                                    <span class="font-medium">Aucun véhicule actuellement stationné.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <Teleport to="body">
            <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md mx-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 mb-1">Confirmer la sortie</h3>
                    <p class="text-sm text-gray-500 mb-6">Vérifiez les informations avant de valider.</p>

                    <div class="bg-gray-50 rounded-2xl divide-y divide-gray-100 mb-6">
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-sm text-gray-500">Immatriculation</span>
                            <span class="text-sm font-bold text-gray-900 uppercase tracking-widest">{{ selected.license_plate }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-sm text-gray-500">Véhicule</span>
                            <span class="text-sm font-semibold text-gray-700">{{ [selected.marque, selected.modele].filter(Boolean).join(' ') || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-sm text-gray-500">Parking</span>
                            <span class="text-sm font-semibold text-gray-700">{{ selected.parking_name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-sm text-gray-500">Entrée</span>
                            <span class="text-sm text-gray-700">{{ formatDate(selected.started_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-sm text-gray-500">Durée</span>
                            <span class="text-sm font-semibold text-gray-800 tabular-nums">
                                {{ formatDuration(liveMinutes) }}
                                <span class="text-xs text-green-600 ml-1 animate-pulse">●</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-4 rounded-b-2xl bg-green-50">
                            <span class="text-base font-bold text-gray-900">Montant à payer</span>
                            <span v-if="liveAmount !== null" class="text-2xl font-black text-green-700 tabular-nums">
                                {{ liveAmount.toLocaleString('fr-FR') }}<span class="text-sm font-semibold ml-1">FCFA</span>
                            </span>
                            <span v-else class="text-sm text-gray-400 italic">Aucun tarif défini</span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="closeModal" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Annuler</button>
                        <button
                            @click="confirmCheckout"
                            :disabled="checkoutForm.processing"
                            class="flex-1 px-4 py-2.5 bg-[#487119] hover:bg-[#3d5e15] disabled:opacity-60 text-white rounded-xl text-sm font-semibold transition-all duration-200 active:scale-95"
                        >
                            <span v-if="checkoutForm.processing">Enregistrement…</span>
                            <span v-else>Valider la sortie</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
