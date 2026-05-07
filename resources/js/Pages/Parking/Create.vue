<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';
import { useReverseGeocode } from '@/Composables/useReverseGeocode';
import { usePlacesAutocomplete } from '@/Composables/usePlacesAutocomplete';
import GoogleMapPicker from '@/Components/GoogleMapPicker.vue';

const props = defineProps({
    parking: Object,
});

const isEditing = !!props.parking;

const form = useForm({
    name: props.parking?.name ?? '',
    address: props.parking?.address ?? '',
    longitude: props.parking?.longitude ?? '',
    latitude: props.parking?.latitude ?? '',
    capacity: props.parking?.capacity ?? '',
    price: props.parking?.price ?? '',
    image: null,
    rates: props.parking?.rates?.map(r => ({
        label:        r.label ?? '',
        from_minutes: r.from_minutes,
        to_minutes:   r.to_minutes ?? '',
        amount:       r.amount,
    })) ?? [],
});

const previewUrl = ref(props.parking?.image ? '/storage/' + props.parking.image : null);
const geoStatus = ref('');
const mapLinkInput = ref('');
const { locationName, reverseGeocode } = useReverseGeocode();
const { searchQuery, suggestions, showSuggestions, isSearching, onInput, selectSuggestion, clearSuggestions } = usePlacesAutocomplete();

async function pickSuggestion(place) {
    const { lat, lng } = await selectSuggestion(place);
    form.latitude  = lat;
    form.longitude = lng;
    geoStatus.value = `Position sélectionnée : ${lat}, ${lng}`;
    reverseGeocode(lat, lng);
}

// Afficher le nom de localisation si les coordonnées existent déjà (mode édition)
if (props.parking?.latitude && props.parking?.longitude) {
    reverseGeocode(props.parking.latitude, props.parking.longitude);
}

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const applyMapLink = () => {
    const val = mapLinkInput.value.trim();
    const atMatch = val.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/);
    const qMatch = val.match(/[?&](?:q|ll)=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
    const plainMatch = val.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
    const match = atMatch || qMatch || plainMatch;
    if (match) {
        form.latitude = match[1];
        form.longitude = match[2];
        geoStatus.value = 'Coordonnees extraites : ' + match[1] + ', ' + match[2];
        reverseGeocode(match[1], match[2]);
    } else {
        geoStatus.value = 'Lien non reconnu. Essayez un lien Google Maps ou "lat, lng".';
    }
};

const locateMe = () => {
    if (!navigator.geolocation) {
        geoStatus.value = 'La geolocalisation n\'est pas supportee par votre navigateur.';
        return;
    }
    geoStatus.value = 'Localisation en cours...';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude  = pos.coords.latitude.toFixed(7);
            form.longitude = pos.coords.longitude.toFixed(7);
            geoStatus.value = 'Position detectee : ' + form.latitude + ', ' + form.longitude;
            reverseGeocode(form.latitude, form.longitude);
        },
        () => {
            geoStatus.value = 'Impossible de recuperer la position. Veuillez verifier les permissions.';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

// ---- Gestion des tarifs par intervalle ----
const addRate = () => {
    form.rates.push({ label: '', from_minutes: 0, to_minutes: '', amount: '' });
};

const removeRate = (index) => {
    form.rates.splice(index, 1);
};

const submit = () => {
    if (isEditing) {
        form.transform((data) => ({ ...data, _method: 'PUT' })).post(route('parkings.update', props.parking.id), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    } else {
        form.post(route('parkings.store'), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Modifier Parking' : 'Nouveau Parking'" />

    <AuthenticatedLayout>
        <template #header>{{ isEditing ? 'Modification' : 'Creation' }}</template>

        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('parkings.index')" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-100 shadow-sm text-gray-500 hover:text-blue-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </Link>
                <h2 class="text-3xl font-black text-gray-900 tracking-tightest">
                    {{ isEditing ? 'Modifier le parking' : 'Ajouter un nouveau parking' }}
                </h2>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-20">
                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-5">
                        <h3 class="font-bold text-lg text-gray-800 border-b border-gray-50 pb-4 mb-2">Details Generaux</h3>
                        
                        <div v-if="isEditing">
                            <InputLabel value="Reference" />
                            <div class="mt-1 px-4 py-2 bg-gray-50 border border-gray-100 rounded-xl text-sm font-mono font-bold text-gray-500 tracking-widest">
                                {{ parking.reference }}
                            </div>
                        </div>

                        <div>
                            <InputLabel for="name" value="Nom du Parking" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="address" value="Adresse physique" />
                            <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.address" />
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-5">
                        <h3 class="font-bold text-lg text-gray-800 border-b border-gray-50 pb-4 mb-2">Capacite &amp; Tarif de base</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="capacity" value="Nombre de places" />
                                <TextInput id="capacity" v-model="form.capacity" type="number" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.capacity" />
                            </div>
                            <div>
                                <InputLabel for="price" value="Tarif de base (FCFA)" />
                                <TextInput id="price" v-model="form.price" type="number" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.price" />
                            </div>
                        </div>
                    </div>

                    <!-- Tarifs par intervalle -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-50 pb-4 mb-2">
                            <h3 class="font-bold text-lg text-gray-800">Tarifs par intervalle</h3>
                            <button
                                type="button"
                                @click="addRate"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-600 font-bold text-xs hover:bg-blue-100 transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                Ajouter un intervalle
                            </button>
                        </div>

                        <div v-if="form.rates.length === 0" class="text-center py-6 text-sm text-gray-400 italic">
                            Aucun tarif par intervalle. Cliquez sur « Ajouter un intervalle » pour en définir.
                        </div>

                        <div
                            v-for="(rate, index) in form.rates"
                            :key="index"
                            class="relative border border-gray-100 rounded-2xl p-4 bg-gray-50/50 space-y-3"
                        >
                            <button
                                type="button"
                                @click="removeRate(index)"
                                class="absolute top-3 right-3 w-7 h-7 flex items-center justify-center rounded-full bg-red-50 text-red-400 hover:bg-red-100 transition-colors"
                                title="Supprimer cet intervalle"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>

                            <div>
                                <InputLabel :for="'rate-label-' + index" value="Libellé (optionnel)" />
                                <TextInput
                                    :id="'rate-label-' + index"
                                    v-model="rate.label"
                                    type="text"
                                    placeholder="ex: 1ère heure, Au-delà de 2h..."
                                    class="mt-1 block w-full text-sm"
                                />
                                <InputError :message="form.errors['rates.' + index + '.label']" />
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <InputLabel :for="'rate-from-' + index" value="De (minutes)" />
                                    <TextInput
                                        :id="'rate-from-' + index"
                                        v-model="rate.from_minutes"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full text-sm"
                                        required
                                    />
                                    <InputError :message="form.errors['rates.' + index + '.from_minutes']" />
                                </div>
                                <div>
                                    <InputLabel :for="'rate-to-' + index" value="À (minutes)" />
                                    <TextInput
                                        :id="'rate-to-' + index"
                                        v-model="rate.to_minutes"
                                        type="number"
                                        min="1"
                                        placeholder="Illimité"
                                        class="mt-1 block w-full text-sm"
                                    />
                                    <InputError :message="form.errors['rates.' + index + '.to_minutes']" />
                                </div>
                                <div>
                                    <InputLabel :for="'rate-amount-' + index" value="Montant (FCFA)" />
                                    <TextInput
                                        :id="'rate-amount-' + index"
                                        v-model="rate.amount"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full text-sm"
                                        required
                                    />
                                    <InputError :message="form.errors['rates.' + index + '.amount']" />
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.rates" />
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-5">
                        <h3 class="font-bold text-lg text-gray-800 border-b border-gray-50 pb-4 mb-2">Geolocalisation</h3>

                        <!-- Recherche de lieu avec autocomplétion -->
                        <div class="relative">
                            <InputLabel value="Rechercher un lieu" />
                            <div class="relative mt-1">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Tapez une adresse, un quartier…"
                                    autocomplete="off"
                                    @input="onInput"
                                    @blur="() => setTimeout(clearSuggestions, 150)"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                                />
                                <span v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </span>
                            </div>
                            <!-- Liste des suggestions -->
                            <ul
                                v-if="showSuggestions && suggestions.length"
                                class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-xl overflow-hidden"
                            >
                                <li
                                    v-for="place in suggestions"
                                    :key="place.place_id"
                                    @mousedown.prevent="pickSuggestion(place)"
                                    class="flex items-start gap-3 px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-50 last:border-b-0"
                                >
                                    <svg class="w-4 h-4 mt-0.5 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-sm font-semibold text-gray-800 truncate">{{ place.structured_formatting?.main_text ?? place.description }}</span>
                                        <span class="text-xs text-gray-400 truncate">{{ place.structured_formatting?.secondary_text ?? '' }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- <div>
                            <InputLabel value="Coller un lien Google Maps" />
                            <div class="flex gap-2 mt-1">
                                <input
                                    v-model="mapLinkInput"
                                    type="text"
                                    placeholder="https://maps.google.com/... ou lat, lng"
                                    class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                                />
                                <button
                                    type="button"
                                    @click="applyMapLink"
                                    class="px-4 py-2 bg-blue-50 text-blue-600 font-bold text-xs rounded-xl hover:bg-blue-100 transition-colors whitespace-nowrap"
                                >
                                    Extraire
                                </button>
                            </div>
                        </div> -->

                        <button
                            type="button"
                            @click="locateMe"
                            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-dashed border-blue-200 text-blue-500 font-bold text-sm hover:bg-blue-50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Geolocaliser ma position actuelle
                        </button>

                        <p v-if="geoStatus" class="text-xs font-medium text-green-600">
                            {{ geoStatus }}
                        </p>
                        <div v-if="locationName" class="flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-xl">
                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="text-xs font-semibold text-blue-700">{{ locationName }}</span>
                        </div>

                        <!-- Carte interactive Google Maps -->
                        <div class="mt-2">
                            <p class="text-xs text-gray-500 font-semibold mb-2">Cliquez sur la carte pour placer le parking :</p>
                            <GoogleMapPicker
                                :lat="form.latitude"
                                :lng="form.longitude"
                                height="300px"
                                @update:lat="val => { form.latitude = val; reverseGeocode(val, form.longitude); }"
                                @update:lng="val => { form.longitude = val; reverseGeocode(form.latitude, val); }"
                            />
                        </div>

                        <!-- Champs latitude/longitude caches mais toujours dans le formulaire -->
                        <input type="hidden" v-model="form.latitude" />
                        <input type="hidden" v-model="form.longitude" />
                        <InputError :message="form.errors.latitude" />
                        <InputError :message="form.errors.longitude" />

                        <p class="text-xs text-gray-400 italic">Cliquez sur la carte, utilisez votre position, ou collez un lien Google Maps.</p>
                    </div>

                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 space-y-5">
                        <h3 class="font-bold text-lg text-gray-800 border-b border-gray-50 pb-4 mb-2">Visuel</h3>

                        <div class="relative group cursor-pointer border-2 border-dashed border-gray-100 rounded-[2rem] p-4 hover:border-blue-200 transition-colors bg-gray-50/50">
                            <input type="file" @change="onImageChange" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*" />
                            <div v-if="!previewUrl" class="flex flex-col items-center py-6">
                                <svg class="w-12 h-12 text-gray-300 group-hover:text-blue-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span class="text-xs font-bold text-gray-400 mt-2 uppercase tracking-widest">Telecharger une photo</span>
                            </div>
                            <div v-else class="relative aspect-video rounded-xl overflow-hidden">
                                <img :src="previewUrl" class="w-full h-full object-cover" alt="">
                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                    <span class="text-white font-bold text-xs uppercase tracking-widest">Changer de photo</span>
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.image" />
                    </div>

                    <div class="flex justify-end gap-4 pt-4">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="w-full justify-center py-4 rounded-[2rem]">
                            {{ isEditing ? 'Confirmer les modifications' : 'Enregistrer le parking' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>