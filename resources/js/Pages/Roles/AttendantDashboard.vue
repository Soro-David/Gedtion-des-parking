<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LicensePlateScanner from '@/Components/LicensePlateScanner.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ }),
    },
});

const filtre = ref('jour');

const montantFiltre = computed(() => {
    if (filtre.value === 'mois') return props.stats.montantMois ?? 0;
    if (filtre.value === 'annee') return props.stats.montantAnnee ?? 0;
    return props.stats.montantJour ?? 0;
});

const nbFiltre = computed(() => {
    if (filtre.value === 'mois') return props.stats.nbMois ?? 0;
    if (filtre.value === 'annee') return props.stats.nbAnnee ?? 0;
    return props.stats.nbJour ?? 0;
});

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n ?? 0) + ' FCFA';

const fmtDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

// ── Modal stationnement ──────────────────────────────────────────
const showModal = ref(false);
const STORAGE_KEY = 'attendant_selected_parking_id';

const assignedParkings = computed(() => props.stats.parkings ?? []);
const activeParkings = computed(() => assignedParkings.value.filter((parking) => parking.is_active));
const blockedParkings = computed(() => assignedParkings.value.filter((parking) => !parking.is_active));
const hasOnlyBlockedAssignedParkings = computed(() => activeParkings.value.length === 0 && blockedParkings.value.length > 0);
const blockedParkingNames = computed(() => blockedParkings.value.map((parking) => parking.name).join(', '));

const sessionForm = useForm({
    parking_id:    '',
    license_plate: '',
    marque:        '',
    modele:        '',
    vehicle_image: null,
});

const isSingleParking = computed(() => activeParkings.value.length === 1);
const showParkingSelector = ref(false);

const selectedParking = computed(() =>
    activeParkings.value.find((p) => p.id === Number(sessionForm.parking_id)) ?? null
);

const showBlockedParkingPopup = (parkingName = blockedParkingNames.value) => {
    Swal.fire({
        icon: 'warning',
        title: 'Parking bloqué',
        text: parkingName
            ? `Le parking ${parkingName} est actuellement bloqué. Aucune action n'est autorisée.`
            : 'Ce parking est actuellement bloqué. Aucune action n\'est autorisée.',
        confirmButtonText: 'Compris',
        confirmButtonColor: '#dc2626',
    });
};

const initParking = () => {
    if (activeParkings.value.length === 0) return;
    if (activeParkings.value.length === 1) {
        sessionForm.parking_id = activeParkings.value[0].id;
        showParkingSelector.value = false;
    } else {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved && activeParkings.value.find((p) => p.id === Number(saved))) {
            sessionForm.parking_id = Number(saved);
            showParkingSelector.value = false;
        } else {
            showParkingSelector.value = true;
        }
    }
};

watch(() => sessionForm.parking_id, (val) => {
    if (val && activeParkings.value.length > 1) {
        localStorage.setItem(STORAGE_KEY, String(val));
        showParkingSelector.value = false;
    }
});

const openModal = () => {
    if (hasOnlyBlockedAssignedParkings.value) {
        showBlockedParkingPopup();
        return;
    }

    sessionForm.reset();
    sessionForm.clearErrors();
    initParking();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const onPlateDetected = (plate) => { sessionForm.license_plate = plate; };
const onImageSelected = (file) => { sessionForm.vehicle_image = file; };
const onImageCleared = () => { sessionForm.vehicle_image = null; };

const submitSession = () => {
    if (selectedParking.value && !selectedParking.value.is_active) {
        showBlockedParkingPopup(selectedParking.value.name);
        return;
    }

    const ticketWindow = window.open('', '_blank');
    sessionForm.post(route('attendant.parking-sessions.store'), {
        forceFormData: true,
        onSuccess: (page) => {
            const sessionId = page.props.flash?.new_session_id;
            if (sessionId && ticketWindow) {
                ticketWindow.location.href = route('attendant.parking-sessions.ticket', sessionId);
            } else if (ticketWindow) {
                ticketWindow.close();
            }
            closeModal();
        },
        onError: () => {
            if (ticketWindow) ticketWindow.close();

            if (sessionForm.errors.parking_id) {
                showBlockedParkingPopup(selectedParking.value?.name);
            }
        },
    });
};

const goToCheckout = () => {
    if (hasOnlyBlockedAssignedParkings.value) {
        showBlockedParkingPopup();
        return;
    }

    router.visit(route('attendant.checkout'));
};
</script>

<template>
    <div class="space-y-8">

        <!-- ── Dette + Montants encaissés (même ligne) ─────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-stretch">

            <!-- Card dette -->
            <div :class="['rounded-2xl border shadow-sm p-6 flex flex-col justify-between', stats.dette > 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200']">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest" :class="stats.dette > 0 ? 'text-red-500' : 'text-green-500'">Montant non reversé (dette)</p>
                        <p class="text-xs mt-0.5" :class="stats.dette > 0 ? 'text-red-400' : 'text-green-400'">Sorties − versements reçus</p>
                    </div>
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', stats.dette > 0 ? 'bg-red-100' : 'bg-green-100']">
                        <svg v-if="stats.dette > 0" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <svg v-else class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <p :class="['text-3xl font-black', stats.dette > 0 ? 'text-red-600' : 'text-green-600']">{{ fmt(stats.dette) }}</p>
                <!-- <div class="mt-3">
                    <Link v-if="stats.dette > 0" :href="route('attendant.reversements.index')"
                        class="inline-block px-4 py-1.5 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-colors">
                        Reverser
                    </Link>
                    <p v-else class="text-xs font-semibold text-green-600">Aucune dette en cours ✓</p>
                </div> -->
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
                <p class="text-sm text-gray-500 mt-1">{{ nbFiltre }} sortie{{ nbFiltre > 1 ? 's' : '' }}</p>
            </div>

        </div>

        <!-- ── Actions rapides ─────────────────────────────────── -->
        <div>
            <h2 class="text-base font-black text-gray-500 uppercase tracking-widest mb-4">Actions rapides</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <button
                    type="button"
                    @click="openModal"
                    class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-blue-200 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm4 4v8m0-8h3a2 2 0 010 4H9"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 text-center">Stationnements</span>
                </button>
                <button
                    type="button"
                    @click="goToCheckout"
                    class="flex flex-col items-center gap-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-green-200 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-green-50 group-hover:bg-green-100 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 text-center">Enregistrer sortie</span>
                </button>
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

    <!-- ── Modal : Enregistrer un stationnement ───────────────── -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @mousedown.self="closeModal">
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="px-8 py-5 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm">Enregistrer un Stationnement</h3>
                            <p class="text-xs text-gray-400">L'immatriculation est obligatoire. Marque et modèle sont optionnels.</p>
                        </div>
                    </div>
                    <button type="button" @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitSession" class="px-8 py-6 space-y-5 overflow-y-auto">

                    <!-- Parking -->
                    <div>
                        <InputLabel value="Parking" class="text-sm font-semibold text-gray-700 mb-1.5" />
                        <div v-if="activeParkings.length === 0" class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-xs font-medium">
                            {{ hasOnlyBlockedAssignedParkings ? 'Votre parking affecté est actuellement bloqué. Aucune action n\'est autorisée.' : 'Aucun parking ne vous est affecté. Contactez votre superviseur.' }}
                        </div>
                        <div v-else-if="isSingleParking" class="flex items-center gap-3 px-4 py-3 rounded-2xl border-2 border-[#143f85] bg-green-50">
                            <div class="w-8 h-8 rounded-lg bg-[#143f85] text-white flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ activeParkings[0].name }}</p>
                                <p class="text-xs text-gray-500">
                                    Réf. {{ activeParkings[0].reference }} ·
                                    <span :class="activeParkings[0].available_spots > 0 ? 'text-[#143f85]' : 'text-red-600'" class="font-semibold">
                                        {{ activeParkings[0].available_spots }} place{{ activeParkings[0].available_spots !== 1 ? 's' : '' }} libre{{ activeParkings[0].available_spots !== 1 ? 's' : '' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div v-else>
                            <div v-if="!showParkingSelector && selectedParking" class="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl border border-gray-200 bg-gray-50">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ selectedParking.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        <span :class="selectedParking.available_spots > 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                                            {{ selectedParking.available_spots }} place{{ selectedParking.available_spots !== 1 ? 's' : '' }} disponible{{ selectedParking.available_spots !== 1 ? 's' : '' }}
                                        </span>
                                    </p>
                                </div>
                                <button type="button" @click="showParkingSelector = true" class="text-xs text-blue-600 font-bold hover:underline shrink-0">Changer</button>
                            </div>
                            <select v-else v-model="sessionForm.parking_id" required
                                class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                                <option value="" disabled>— Choisir un parking —</option>
                                <option v-for="p in activeParkings" :key="p.id" :value="p.id" :disabled="p.available_spots <= 0">
                                    {{ p.name }} ({{ p.available_spots }} place{{ p.available_spots !== 1 ? 's' : '' }} libre{{ p.available_spots !== 1 ? 's' : '' }})
                                </option>
                            </select>
                        </div>
                        <InputError class="mt-1.5" :message="sessionForm.errors.parking_id" />
                    </div>

                    <!-- Scanner IA -->
                    <div>
                        <InputLabel value="Photo du véhicule (optionnelle — détection IA de la plaque)" class="text-sm font-semibold text-gray-700 mb-1.5" />
                        <LicensePlateScanner :loading="sessionForm.processing" @detected="onPlateDetected" @image-selected="onImageSelected" @clear="onImageCleared" />
                        <InputError class="mt-1.5" :message="sessionForm.errors.vehicle_image" />
                    </div>

                    <!-- Immatriculation -->
                    <div>
                        <InputLabel for="modal-license-plate" value="Immatriculation *" class="text-sm font-semibold text-gray-700 mb-1.5" />
                        <TextInput id="modal-license-plate" type="text" v-model="sessionForm.license_plate" required placeholder="ex: AA-000-BB"
                            class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-blue-500/20 transition-all uppercase" />
                        <InputError class="mt-1.5" :message="sessionForm.errors.license_plate" />
                    </div>

                    <!-- Marque & Modèle -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel for="modal-marque" value="Marque (optionnelle)" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput id="modal-marque" type="text" v-model="sessionForm.marque" placeholder="ex: Toyota"
                                class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-blue-500/20 transition-all" />
                            <InputError class="mt-1.5" :message="sessionForm.errors.marque" />
                        </div>
                        <div>
                            <InputLabel for="modal-modele" value="Modèle (optionnel)" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput id="modal-modele" type="text" v-model="sessionForm.modele" placeholder="ex: Corolla"
                                class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-blue-500/20 transition-all" />
                            <InputError class="mt-1.5" :message="sessionForm.errors.modele" />
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-blue-50 border border-blue-100 text-blue-700 text-xs font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        La date et l'heure de stationnement seront enregistrées automatiquement.
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-4">
                        <button type="button" @click="closeModal"
                            class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                            Annuler
                        </button>
                        <PrimaryButton
                            class="text-white px-7 py-2.5 rounded-xl font-bold shadow-md transition-all duration-200 hover:-translate-y-0.5 active:scale-95"
                            :class="{ 'opacity-50 cursor-not-allowed': sessionForm.processing }"
                            :disabled="sessionForm.processing || activeParkings.length === 0">
                            <span v-if="sessionForm.processing">Enregistrement...</span>
                            <span v-else>Enregistrer le Stationnement</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

</template>
