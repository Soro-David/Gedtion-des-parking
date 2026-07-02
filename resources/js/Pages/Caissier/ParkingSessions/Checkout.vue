<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import jsQR from 'jsqr';
import Swal from 'sweetalert2';

const props = defineProps({
    plate:    { type: String, default: '' },
    sessions: { type: Array, default: () => [] },
    rates:    { type: Array, default: () => [] },
});

// Filtre
const search = ref(props.plate);
const searchInput = ref(null);

const doSearch = () => {
    router.get(route('caissier.checkout'), { plate: search.value }, { preserveState: true, replace: true });
};

// Filtre côté client en temps réel
const filtered = computed(() => {
    const q = search.value.trim().toUpperCase();
    if (!q) return props.sessions;
    return props.sessions.filter(s => s.license_plate.includes(q));
});

// Calcul JS du montant basé sur l'heure actuelle
const computeAmount = (parkingId, parkingPrice) => {
    const now = new Date();
    const currentTime = now.toTimeString().substring(0, 5);
    const match = props.rates
        .filter(r => r.parking_id === parkingId && r.from_time <= currentTime && r.to_time > currentTime);
    if (match.length > 0) return match[0].amount;
    return parkingPrice ?? null;
};

// Sélection + minuterie temps réel
const selected = ref(null);
const checkoutForm = useForm({ session_id: '' });
const liveMinutes = ref(0);
const liveAmount  = ref(null);
let timer = null;

const tickLive = () => {
    if (!selected.value) return;
    const mins = Math.ceil((Date.now() - new Date(selected.value.started_at).getTime()) / 60000);
    liveMinutes.value = mins;
    liveAmount.value  = computeAmount(selected.value.parking_id, selected.value.parking_price);
};

const modalOpen = ref(false);

const showBlockedParkingPopup = (parkingName = selected.value?.parking_name) => {
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

const openModal = (session) => {
    if (!session.parking_is_active) {
        showBlockedParkingPopup(session.parking_name);
        return;
    }

    clearInterval(timer);
    selected.value = session;
    checkoutForm.session_id = session.id;
    tickLive();
    timer = setInterval(tickLive, 1000);
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    selected.value = null;
    clearInterval(timer);
    timer = null;
};

// Auto-ouverture du modal quand exactement 1 résultat
watch(filtered, (newFiltered) => {
    if (newFiltered.length === 1) {
        openModal(newFiltered[0]);
    } else if (newFiltered.length === 0) {
        if (!checkoutForm.processing) closeModal();
    }
}, { immediate: true });

const confirmCheckout = () => {
    if (selected.value && !selected.value.parking_is_active) {
        showBlockedParkingPopup(selected.value.parking_name);
        return;
    }

    const plate = selected.value?.license_plate ?? '';

    // Ouvrir un onglet vide immédiatement (synchrone = non bloqué par le navigateur)
    const receiptWindow = window.open('', '_blank');

    checkoutForm.post(route('caissier.checkout.store'), {
        onSuccess: (page) => {
            closeModal();
            search.value = '';
            const sessionId = page.props.flash?.checked_out_session_id;
            if (sessionId && receiptWindow) {
                receiptWindow.location.href = route('caissier.parking-sessions.receipt', sessionId);
            } else if (receiptWindow) {
                receiptWindow.close();
            }
            Swal.fire({
                icon: 'success',
                title: 'Sortie enregistrée !',
                html: `Le véhicule <strong>${plate}</strong> a bien été enregistré en sortie.`,
                confirmButtonText: 'OK',
                confirmButtonColor: '#16a34a',
                timer: 4000,
                timerProgressBar: true,
            });
        },
        onError: (errors) => {
            if (receiptWindow) receiptWindow.close();

            if (errors.session_id) {
                showBlockedParkingPopup(selected.value?.parking_name);
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Une erreur est survenue lors de l\'enregistrement de la sortie.',
                confirmButtonText: 'Réessayer',
                confirmButtonColor: '#dc2626',
            });
        },
    });
};

// Formatage
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

// ─── Scanner QR inline ─────────────────────────────────────────
const scannerActive = ref(false);
const videoRef      = ref(null);
const canvasRef     = ref(null);
let   stream        = null;
let   rafId         = null;

const startScanner = async () => {
    if (scannerActive.value) return;
    scannerActive.value = true;
    await new Promise(r => setTimeout(r, 150));
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        videoRef.value.srcObject = stream;
        await videoRef.value.play();
        scanFrame();
    } catch (e) {
        scannerActive.value = false;
        Swal.fire({
            icon: 'error',
            title: 'Caméra inaccessible',
            text: 'Impossible d\'accéder à la caméra. Vérifiez les permissions du navigateur.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc2626',
        });
    }
};

const stopScanner = () => {
    scannerActive.value = false;
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
};

const scanFrame = () => {
    const video  = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas || !scannerActive.value) return;

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code && code.data) {
            const plate = code.data.trim().toUpperCase();
            stopScanner();
            search.value = plate;
            // watch(filtered) gère l'auto-sélection si 1 résultat
            const match = props.sessions.find(s => s.license_plate === plate);
            if (!match) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Véhicule introuvable',
                    html: `Aucun stationnement actif trouvé pour <strong>${plate}</strong>.`,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ea580c',
                });
            }
            return;
        }
    }
    rafId = requestAnimationFrame(scanFrame);
};

// Démarrage automatique du scanner à l'ouverture de la page
onMounted(() => startScanner());
onUnmounted(() => { stopScanner(); clearInterval(timer); });
</script>

<template>
    <Head title="Enregistrer une sortie" />

    <AuthenticatedLayout>
        <template #header>Enregistrement des sorties</template>

        <!-- En-tête -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Enregistrer une sortie</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <span class="font-bold text-gray-700">{{ sessions.length }}</span> véhicule(s) actuellement stationné(s).
                </p>
            </div>
        </div>

        <!-- Layout 2 colonnes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <!-- ── Colonne gauche : Scanner QR ── -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-bold text-gray-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1M4 12h1m14 0h1M6.343 6.343l.707.707M16.95 16.95l.707.707M6.343 17.657l.707-.707M16.95 7.05l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                        </svg>
                        Scanner QR du ticket
                    </p>
                    <button
                        v-if="!scannerActive"
                        type="button"
                        @click="startScanner"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
                    >
                        ↻ Relancer
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="stopScanner"
                        class="text-xs font-semibold text-gray-400 hover:text-red-500 transition-colors"
                    >
                        Arrêter
                    </button>
                </div>

                <!-- Viewfinder -->
                <div class="relative rounded-2xl overflow-hidden bg-gray-900" style="aspect-ratio:1/1;">
                    <video
                        v-show="scannerActive"
                        ref="videoRef"
                        class="w-full h-full object-cover"
                        muted
                        playsinline
                    ></video>

                    <!-- Placeholder caméra inactive -->
                    <div
                        v-if="!scannerActive"
                        class="w-full h-full flex flex-col items-center justify-center gap-3 text-gray-500"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-xs opacity-50">Caméra inactive</span>
                    </div>

                    <!-- Viseur (quand actif) -->
                    <div v-if="scannerActive" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="relative" style="width:60%; height:60%;">
                            <span class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-indigo-400 rounded-tl-lg"></span>
                            <span class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-indigo-400 rounded-tr-lg"></span>
                            <span class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-indigo-400 rounded-bl-lg"></span>
                            <span class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-indigo-400 rounded-br-lg"></span>
                            <div class="absolute left-0 right-0 h-0.5 bg-indigo-400/70 animate-scan-line"></div>
                        </div>
                    </div>

                    <!-- Badge actif -->
                    <div v-if="scannerActive" class="absolute top-2 right-2 flex items-center gap-1.5 bg-black/50 backdrop-blur-sm rounded-full px-2.5 py-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-[10px] text-white font-semibold">Scan actif</span>
                    </div>
                </div>

                <canvas ref="canvasRef" class="hidden"></canvas>
                <p class="text-[11px] text-gray-400 text-center mt-2">Pointez vers le QR code du ticket</p>
            </div>

            <!-- ── Colonne droite : Saisie + Détails automatiques ── -->
            <div class="flex flex-col gap-6">

                <!-- Saisie manuelle immatriculation -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Saisir l'immatriculation
                    </p>
                    <form @submit.prevent="doSearch" class="space-y-3">
                        <div class="relative">
                            <input
                                ref="searchInput"
                                v-model="search"
                                type="text"
                                placeholder="ex: AA-000-BB"
                                class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl text-xl uppercase font-black tracking-[0.2em] placeholder:normal-case placeholder:font-normal placeholder:tracking-normal placeholder:text-gray-300 focus:ring-0 focus:border-indigo-400 outline-none transition-colors text-center"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="search = ''"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <PrimaryButton type="submit" class="w-full justify-center py-3 rounded-2xl text-base font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Rechercher le véhicule
                        </PrimaryButton>
                    </form>

                    <!-- Plusieurs résultats → liste cliquable -->
                    <div v-if="search && filtered.length > 1" class="mt-4 space-y-2">
                        <p class="text-xs text-gray-500 font-semibold mb-1">
                            {{ filtered.length }} véhicules trouvés — sélectionnez :
                        </p>
                        <button
                            v-for="s in filtered"
                            :key="s.id"
                            @click="openModal(s)"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all text-left"
                        >
                            <span class="font-black uppercase tracking-widest text-sm text-gray-900">{{ s.license_plate }}</span>
                            <span class="text-xs text-gray-500">{{ s.parking_name || '—' }}</span>
                        </button>
                    </div>

                    <!-- Aucun résultat -->
                    <div v-if="search && filtered.length === 0" class="mt-4 p-4 rounded-2xl bg-orange-50 border border-orange-200 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <p class="text-sm text-orange-700 font-semibold">Aucun véhicule trouvé pour « <span class="uppercase tracking-widest">{{ search }}</span> »</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Modal de confirmation ── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="modalOpen && selected"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                    @click.self="closeModal"
                >
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                        appear
                    >
                        <div v-if="modalOpen && selected" class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md mx-4">

                            <!-- Icône + Titre -->
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Confirmer la sortie</h3>
                                    <p class="text-sm text-gray-500">Vérifiez les informations avant de valider.</p>
                                </div>
                                <button @click="closeModal" class="ml-auto text-gray-300 hover:text-gray-500 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Détails -->
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

                            <!-- Boutons -->
                            <div class="flex gap-3">
                                <button
                                    @click="closeModal"
                                    class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Annuler
                                </button>
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
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>
