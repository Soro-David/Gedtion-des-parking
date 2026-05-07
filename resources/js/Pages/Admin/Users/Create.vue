<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import UserFormFields from '@/Components/Layout/UserFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import CancelButton from '@/Components/CancelButton.vue';
import ParkingSelector from '@/Components/ParkingSelector.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';

defineProps({
    parkings: { type: Array, default: () => [] },
});

const form = useForm({
    role: '',
    name: '',
    first_name: '',
    email: '',
    phone: '',
    address: '',
    emergency_contact: '',
    emergency_relationship: '',
    emergency_name: '',
    parking_ids: [],
});

const roleOptions = [
    {
        key: 'supervisor',
        label: 'Superviseur',
        description: 'Gère les agents et les caissiers d\'un parking.',
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        color: 'border-purple-300 bg-purple-50 text-purple-700',
        activeColor: 'border-purple-600 bg-purple-100 text-purple-800 ring-2 ring-purple-300',
        dot: 'bg-purple-500',
    },
    {
        key: 'attendant',
        label: 'Agent',
        description: 'Enregistre les entrées et sorties des véhicules.',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        color: 'border-blue-300 bg-blue-50 text-blue-700',
        activeColor: 'border-blue-600 bg-blue-100 text-blue-800 ring-2 ring-blue-300',
        dot: 'bg-blue-500',
    },
    {
        key: 'caissier',
        label: 'Caissier(ère)',
        description: 'Gère les paiements et la caisse du parking.',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        color: 'border-green-300 bg-green-50 text-green-700',
        activeColor: 'border-green-600 bg-green-100 text-green-800 ring-2 ring-green-300',
        dot: 'bg-green-500',
    },
];

const needsParking = computed(() =>
    form.role === 'attendant' || form.role === 'caissier'
);

const selectedRole = computed(() => roleOptions.find(r => r.key === form.role));

const submit = () => {
    form.post(route('admin.users.store'));
};
</script>

<template>
    <Head title="Ajouter un Utilisateur" />

    <AuthenticatedLayout>
        <template #header>Nouvel Utilisateur</template>

        <div class="max-w-4xl mx-auto pb-20">
            <!-- Titre + retour -->
            <div class="flex items-center gap-3 mb-8">
                <Link
                    :href="route('admin.users.index')"
                    class="p-2 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200"
                    title="Retour"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tightest">Ajouter un Utilisateur</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Sélectionnez un rôle puis remplissez les informations.</p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <form @submit.prevent="submit" class="p-8 space-y-8">

                    <!-- ── Sélection du rôle ────────────────────────── -->
                    <div>
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#143f85]"></span>
                            Rôle de l'utilisateur
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button
                                v-for="r in roleOptions"
                                :key="r.key"
                                type="button"
                                @click="form.role = r.key"
                                :class="[
                                    'relative flex flex-col gap-3 rounded-2xl border-2 p-5 text-left transition-all duration-200 hover:shadow-sm',
                                    form.role === r.key ? r.activeColor : 'border-gray-200 bg-white hover:border-gray-300',
                                ]"
                            >
                                <!-- Indicateur sélection -->
                                <div class="absolute top-3 right-3">
                                    <div :class="['w-4 h-4 rounded-full border-2 transition-all', form.role === r.key ? r.dot + ' border-transparent' : 'border-gray-300']"></div>
                                </div>
                                <!-- Icône -->
                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', form.role === r.key ? 'bg-white/60' : 'bg-gray-100']">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="r.icon" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-sm">{{ r.label }}</p>
                                    <p class="text-xs mt-0.5 opacity-75">{{ r.description }}</p>
                                </div>
                            </button>
                        </div>
                        <InputError :message="form.errors.role" class="mt-2" />
                    </div>

                    <!-- ── Informations personnelles (visibles si rôle sélectionné) ── -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-if="form.role" class="space-y-8">
                            <!-- Rappel du rôle sélectionné -->
                            <div :class="['flex items-center gap-2 px-4 py-3 rounded-2xl border text-sm font-medium', selectedRole?.color]">
                                <div :class="['w-2 h-2 rounded-full', selectedRole?.dot]"></div>
                                Vous créez un(e) <strong>{{ selectedRole?.label }}</strong>
                            </div>

                            <!-- Champs communs -->
                            <UserFormFields :form="form" />

                            <!-- Parkings (Agent & Caissier seulement) -->
                            <div v-if="needsParking">
                                <InputLabel value="Parkings affectés" class="text-sm font-semibold text-gray-700 mb-3" />
                                <ParkingSelector
                                    :parkings="parkings"
                                    v-model="form.parking_ids"
                                    :error="form.errors.parking_ids"
                                />
                            </div>

                            <!-- Boutons -->
                            <div class="pt-8 border-t border-gray-100 flex items-center justify-between gap-4">
                                <CancelButton :href="route('admin.users.index')" />
                                <PrimaryButton
                                    class="px-10 py-4 rounded-[2rem] font-black text-sm"
                                    :disabled="form.processing"
                                >
                                    Enregistrer le {{ selectedRole?.label }}
                                </PrimaryButton>
                            </div>
                        </div>
                    </transition>

                    <!-- Message si aucun rôle sélectionné -->
                    <div v-if="!form.role" class="flex flex-col items-center justify-center py-10 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">Sélectionnez un rôle ci-dessus pour continuer.</p>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
