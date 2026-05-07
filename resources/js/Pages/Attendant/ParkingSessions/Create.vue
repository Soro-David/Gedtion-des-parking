<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import LicensePlateScanner from '@/Components/LicensePlateScanner.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, watch, onMounted, ref } from 'vue';

const props = defineProps({
    parkings: Array,
});

const STORAGE_KEY = 'attendant_selected_parking_id';

const showSelector = ref(false);

const form = useForm({
    parking_id:    '',
    license_plate: '',
    marque:        '',
    modele:        '',
    vehicle_image: null,
});

// Parking actuellement sélectionné (objet complet)
const selectedParking = computed(() =>
    props.parkings.find((p) => p.id === Number(form.parking_id)) ?? null
);

// Vrai si le parking est figé (1 seul affecté → pas de changement possible)
const isSingleParking = computed(() => props.parkings.length === 1);

onMounted(() => {
    if (props.parkings.length === 0) return;

    if (props.parkings.length === 1) {
        // 1 seul parking affecté → sélection automatique, pas besoin du sélecteur
        form.parking_id = props.parkings[0].id;
        showSelector.value = false;
    } else {
        // Plusieurs parkings → lire la mémorisation localStorage
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const savedId = Number(saved);
            if (props.parkings.find((p) => p.id === savedId)) {
                form.parking_id = savedId;
                showSelector.value = false; // parking mémorisé → masquer sélecteur
            } else {
                showSelector.value = true; // mémorisé mais n'existe plus → afficher sélecteur
            }
        } else {
            showSelector.value = true; // aucun mémorisé → afficher sélecteur
        }
    }
});

// Mémoriser la sélection dans localStorage (agents avec plusieurs parkings uniquement)
watch(
    () => form.parking_id,
    (val) => {
        if (val && props.parkings.length > 1) {
            localStorage.setItem(STORAGE_KEY, String(val));
            showSelector.value = false;
        }
    }
);

const changeParking = () => {
    form.parking_id = '';
    showSelector.value = true;
};

const submit = () => {
    form.post(route('attendant.parking-sessions.store'), {
        forceFormData: true,
    });
};

// L'IA a détecté une plaque → l'appliquer au champ immatriculation
const onPlateDetected = (plate) => {
    form.license_plate = plate;
};

// L'utilisateur a sélectionné/pris une image
const onImageSelected = (file) => {
    form.vehicle_image = file;
};

// L'utilisateur a effacé l'image
const onImageCleared = () => {
    form.vehicle_image = null;
};
</script>

<template>
    <Head title="Enregistrer un Stationnement" />

    <AuthenticatedLayout>
        <template #header>Nouveau Stationnement</template>

        <!-- En-tête -->
        <div class="flex items-center gap-3 mb-8">
            <Link
                :href="route('attendant.parking-sessions.index')"
                class="p-2 rounded-xl text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition-all duration-200"
                title="Retour"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Enregistrer un Stationnement</h2>
                <p class="text-sm text-gray-500 mt-0.5">Saisissez les informations du véhicule à stationner.</p>
            </div>
        </div>

        <!-- Formulaire -->
        <div class="flex justify-center">
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">

                <!-- Card header -->
                <div class="px-8 py-5 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Informations du Véhicule</h3>
                        <p class="text-xs text-gray-400">L'immatriculation est obligatoire. Marque et modèle sont optionnels.</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="px-8 py-6 space-y-5">

                    <!-- Zone parking -->
                    <div>
                        <InputLabel value="Parking" class="text-sm font-semibold text-gray-700 mb-1.5" />

                        <!-- Pas de parking affecté -->
                        <div v-if="parkings.length === 0" class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z" />
                            </svg>
                            Aucun parking ne vous est affecté. Contactez votre superviseur.
                        </div>

                        <!-- Parking sélectionné / auto-sélectionné → afficher comme carte -->
                        <div v-else-if="selectedParking && !showSelector" class="flex items-center justify-between px-4 py-3 rounded-2xl border-2 border-[#487119] bg-green-50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-[#487119] text-white flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ selectedParking.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        Réf. {{ selectedParking.reference }} ·
                                        <span :class="selectedParking.available_spots > 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                                            {{ selectedParking.available_spots }} place{{ selectedParking.available_spots !== 1 ? 's' : '' }} libre{{ selectedParking.available_spots !== 1 ? 's' : '' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <!-- Bouton "Changer" uniquement si plusieurs parkings -->
                            <button
                                v-if="!isSingleParking"
                                type="button"
                                @click="changeParking"
                                class="text-xs font-semibold text-[#487119] hover:text-[#3d5e15] hover:underline transition-colors"
                            >
                                Changer
                            </button>
                        </div>

                        <!-- Sélecteur (plusieurs parkings, aucun mémorisé ou après "Changer") -->
                        <div v-else-if="showSelector">
                            <select
                                id="parking_id"
                                v-model="form.parking_id"
                                required
                                class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition-all"
                            >
                                <option value="" disabled>— Choisir un parking —</option>
                                <option
                                    v-for="p in parkings"
                                    :key="p.id"
                                    :value="p.id"
                                    :disabled="p.available_spots <= 0"
                                >
                                    {{ p.name }} ({{ p.available_spots }} place{{ p.available_spots !== 1 ? 's' : '' }} libre{{ p.available_spots !== 1 ? 's' : '' }})
                                </option>
                            </select>
                        </div>

                        <InputError class="mt-1.5" :message="form.errors.parking_id" />
                    </div>

                    <!-- Photo du véhicule + OCR -->
                    <div>
                        <InputLabel value="Photo du véhicule (optionnelle — détection IA de la plaque)" class="text-sm font-semibold text-gray-700 mb-1.5" />
                        <LicensePlateScanner
                            :loading="form.processing"
                            @detected="onPlateDetected"
                            @image-selected="onImageSelected"
                            @clear="onImageCleared"
                        />
                        <InputError class="mt-1.5" :message="form.errors.vehicle_image" />
                    </div>

                    <!-- Immatriculation -->
                    <div>
                        <InputLabel for="license_plate" value="Immatriculation *" class="text-sm font-semibold text-gray-700 mb-1.5" />
                        <TextInput
                            id="license_plate"
                            type="text"
                            class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all uppercase"
                            v-model="form.license_plate"
                            required
                            placeholder="ex: AA-000-BB"
                        />
                        <InputError class="mt-1.5" :message="form.errors.license_plate" />
                    </div>

                    <!-- Marque & Modèle -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel for="marque" value="Marque (optionnelle)" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput
                                id="marque"
                                type="text"
                                class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all"
                                v-model="form.marque"
                                placeholder="ex: Toyota"
                            />
                            <InputError class="mt-1.5" :message="form.errors.marque" />
                        </div>
                        <div>
                            <InputLabel for="modele" value="Modèle (optionnel)" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput
                                id="modele"
                                type="text"
                                class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all"
                                v-model="form.modele"
                                placeholder="ex: Corolla"
                            />
                            <InputError class="mt-1.5" :message="form.errors.modele" />
                        </div>
                    </div>

                    <!-- Info date/heure automatique -->
                    <div class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-blue-50 border border-blue-100 text-blue-700 text-xs font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        La date et l'heure de stationnement seront enregistrées automatiquement.
                        Le statut <strong class="mx-1">Occupé</strong> sera attribué à la place automatiquement.
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-4">
                        <Link
                            :href="route('attendant.parking-sessions.index')"
                            class="px-5 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200"
                        >
                            Annuler
                        </Link>
                        <PrimaryButton
                            class="text-white px-7 py-2.5 rounded-xl font-bold shadow-md transition-all duration-200 hover:-translate-y-0.5 active:scale-95"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing || parkings.length === 0"
                        >
                            <span v-if="form.processing">Enregistrement...</span>
                            <span v-else>Enregistrer le Stationnement</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
