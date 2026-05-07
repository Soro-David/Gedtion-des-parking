<script setup>
import { ref, nextTick, onUnmounted } from 'vue';
import { useLicensePlateOcr } from '@/Composables/useLicensePlateOcr';

const props = defineProps({
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['detected', 'image-selected', 'clear']);

const { isLoading, error, extractPlate } = useLicensePlateOcr();

const previewUrl    = ref(null);
const fileInput     = ref(null);
const detectedPlate = ref(null);

// ─── Gestion du flux caméra ───────────────────────────────────────────────
const showCamera  = ref(false);
const videoEl     = ref(null);
const stream      = ref(null);
const cameraError = ref(null);

const stopStream = () => {
    if (stream.value) {
        stream.value.getTracks().forEach((t) => t.stop());
        stream.value = null;
    }
};

const openCamera = async () => {
    cameraError.value = null;
    showCamera.value  = true;
    await nextTick();
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        });
        if (videoEl.value) {
            videoEl.value.srcObject = stream.value;
        }
    } catch (e) {
        cameraError.value = 'Caméra inaccessible : ' + (e.message || e.name);
        stopStream();
    }
};

const capturePhoto = () => {
    if (!videoEl.value) return;
    const canvas = document.createElement('canvas');
    canvas.width  = videoEl.value.videoWidth;
    canvas.height = videoEl.value.videoHeight;
    canvas.getContext('2d').drawImage(videoEl.value, 0, 0);
    stopStream();
    showCamera.value = false;
    canvas.toBlob(async (blob) => {
        const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
        await handleFile(file);
    }, 'image/jpeg', 0.92);
};

const closeCamera = () => {
    stopStream();
    showCamera.value  = false;
    cameraError.value = null;
};

onUnmounted(() => stopStream());

// ─── Gestion du fichier sélectionné ──────────────────────────────────────
const handleFile = async (file) => {
    if (!file) return;
    previewUrl.value    = URL.createObjectURL(file);
    detectedPlate.value = null;
    error.value         = null;
    emit('image-selected', file);
    const plate = await extractPlate(file);
    if (plate) {
        detectedPlate.value = plate;
        emit('detected', plate);
    }
};

const onFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) handleFile(file);
};

const clearImage = () => {
    previewUrl.value    = null;
    detectedPlate.value = null;
    error.value         = null;
    if (fileInput.value) fileInput.value.value = '';
    emit('clear');
};
</script>

<template>
    <div class="space-y-3">
        <!-- Zone de prévisualisation / sélection -->
        <div
            class="relative border-2 border-dashed border-gray-200 rounded-2xl overflow-hidden bg-gray-50 hover:border-orange-300 transition-colors"
            :class="previewUrl ? 'border-orange-300' : ''"
        >
            <!-- Preview -->
            <img
                v-if="previewUrl"
                :src="previewUrl"
                alt="Aperçu du véhicule"
                class="w-full object-cover max-h-56 rounded-2xl"
            />

            <!-- Placeholder -->
            <div v-else class="flex flex-col items-center justify-center py-8 px-4 gap-2 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-xs font-medium text-center">Photo du véhicule (optionnelle)<br>
                    <span class="text-[11px] text-gray-300">L'IA détectera la plaque automatiquement</span>
                </p>
            </div>

            <!-- Bouton supprimer (si image chargée) -->
            <button
                v-if="previewUrl"
                type="button"
                @click="clearImage"
                class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-full bg-white/90 text-gray-500 hover:text-red-500 shadow transition-colors"
                title="Supprimer l'image"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Boutons d'action -->
        <div class="flex gap-2">
            <!-- Ouvrir la galerie / fichier -->
            <label class="flex-1 cursor-pointer">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onFileChange"
                    :disabled="loading || isLoading"
                />
                <span class="flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-xs font-semibold transition-colors w-full"
                      :class="(loading || isLoading) ? 'opacity-50 cursor-not-allowed' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Galerie
                </span>
            </label>

            <!-- Ouvrir la caméra (getUserMedia) -->
            <button
                type="button"
                :disabled="loading || isLoading"
                @click="openCamera"
                class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl border border-orange-200 bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Caméra
            </button>
        </div>

        <!-- ─── Flux caméra inline ──────────────────────────────────────── -->
        <div v-if="showCamera" class="rounded-2xl border border-orange-200 overflow-hidden bg-black">
            <!-- Erreur d'accès caméra -->
            <div v-if="cameraError" class="px-4 py-6 text-center text-red-400 text-xs font-medium">
                {{ cameraError }}
                <br>
                <button type="button" @click="closeCamera" class="mt-3 px-4 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition-colors">Fermer</button>
            </div>

            <!-- Flux vidéo -->
            <template v-else>
                <video
                    ref="videoEl"
                    autoplay
                    playsinline
                    muted
                    class="w-full max-h-64 object-cover"
                />
                <div class="flex items-center justify-between gap-2 px-4 py-3 bg-black/80">
                    <button
                        type="button"
                        @click="closeCamera"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-600 text-gray-300 text-xs font-semibold hover:bg-gray-800 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Annuler
                    </button>
                    <button
                        type="button"
                        @click="capturePhoto"
                        class="flex items-center gap-2 px-5 py-1.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold transition-colors shadow"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        </svg>
                        Prendre la photo
                    </button>
                </div>
            </template>
        </div>

        <!-- Statut IA -->
        <div v-if="isLoading" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-orange-50 border border-orange-100 text-orange-600 text-xs font-medium">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            Analyse de la plaque en cours…
        </div>

        <div v-else-if="detectedPlate" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Plaque détectée :
            <span class="ml-1 px-2 py-0.5 bg-green-100 rounded font-black tracking-widest uppercase text-green-800">
                {{ detectedPlate }}
            </span>
            <span class="text-green-500 font-normal ml-1">(appliquée automatiquement)</span>
        </div>

        <div v-else-if="error" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 border border-red-100 text-red-600 text-xs font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z" />
            </svg>
            {{ error }}
        </div>
    </div>
</template>
