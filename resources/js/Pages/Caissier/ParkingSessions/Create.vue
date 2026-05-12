<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import LicensePlateScanner from '@/Components/LicensePlateScanner.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import CancelButton from '@/Components/CancelButton.vue';

const props = defineProps({
    parkings: Array,
});

const form = useForm({
    parking_id:    '',
    license_plate: '',
    marque:        '',
    modele:        '',
    vehicle_image: null,
});

// Caissier : auto-sélection si 1 seul parking, sinon sélection manuelle à chaque fois
const isSingleParking = computed(() => props.parkings.length === 1);

onMounted(() => {
    if (props.parkings.length === 1) {
        form.parking_id = props.parkings[0].id;
    }
});

const selectedParking = computed(() =>
    props.parkings.find((p) => p.id === Number(form.parking_id)) ?? null
);

// L'IA a détecté une plaque → l'appliquer au champ immatriculation
const onPlateDetected = (plate) => {
    form.license_plate = plate;
};

// L'utilisateur a sélectionné/pris une image → la stocker dans le form
const onImageSelected = (file) => {
    form.vehicle_image = file;
};

// L'utilisateur a effacé l'image
const onImageCleared = () => {
    form.vehicle_image = null;
};

const submit = () => {
    form.post(route('caissier.parking-sessions.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Enregistrer un Stationnement" />

    <AuthenticatedLayout>
        <template #header>Nouveau Stationnement</template>

        <!-- En-tête -->
        <div class="flex items-center gap-3 mb-8">
            <Link
                :href="route('caissier.parking-sessions.index')"
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

                        <!-- 1 seul parking → carte fixe, pas de changement -->
                        <div v-else-if="isSingleParking" class="flex items-center gap-3 px-4 py-3 rounded-2xl border-2 border-[#143f85] bg-green-50">
                            <div class="w-8 h-8 rounded-lg bg-[#143f85] text-white flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ parkings[0].name }}</p>
                                <p class="text-xs text-gray-500">
                                    Réf. {{ parkings[0].reference }} ·
                                    <span :class="parkings[0].available_spots > 0 ? 'text-[#143f85]' : 'text-red-600'" class="font-semibold">
                                        {{ parkings[0].available_spots }} place{{ parkings[0].available_spots !== 1 ? 's' : '' }} libre{{ parkings[0].available_spots !== 1 ? 's' : '' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Plusieurs parkings → sélection manuelle à chaque fois -->
                        <div v-else>
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

                            <!-- Résumé places du parking choisi -->
                            <div
                                v-if="selectedParking"
                                class="mt-2 flex items-center gap-2 text-xs font-medium"
                                :class="selectedParking.available_spots > 0 ? 'text-green-600' : 'text-red-600'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ selectedParking.available_spots }} / {{ selectedParking.capacity }} places disponibles
                            </div>
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
                        <CancelButton
                            :href="route('caissier.parking-sessions.index')"
                        >
                            Annuler
                        </CancelButton>
                        <PrimaryButton
                            class=" text-white px-7 py-2.5 rounded-xl font-bold shadow-md transition-all duration-200 hover:-translate-y-0.5 active:scale-95"
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
