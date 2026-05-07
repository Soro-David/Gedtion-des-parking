<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    license_plate: '',
});

const submit = () => {
    form.post(route('attendant.drivers.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Inscrire un Conducteur" />

    <AuthenticatedLayout>
        <template #header>Nouveau Conducteur</template>

        <!-- En-tête de page -->
        <div class="flex items-center gap-3 mb-8">
            <Link :href="route('attendant.drivers.index')" class="p-2 rounded-xl text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition-all duration-200" title="Retour">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Nouveau Conducteur</h2>
                <p class="text-sm text-gray-500 mt-0.5">Remplissez les informations du conducteur.</p>
            </div>
        </div>

        <!-- Formulaire -->
        <div class="flex justify-center">
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Informations du Conducteur</h3>
                        <p class="text-xs text-gray-400">Tous les champs sont obligatoires.</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="px-8 py-6 space-y-5">
                    <div>
                        <InputLabel for="name" value="Nom Complet" class="text-sm font-semibold text-gray-700 mb-1.5" />
                        <TextInput id="name" type="text" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all" v-model="form.name" required autofocus placeholder="ex: Fatou Diallo" />
                        <InputError class="mt-1.5" :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel for="email" value="Email" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput id="email" type="email" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all" v-model="form.email" required placeholder="conducteur@mail.com" />
                            <InputError class="mt-1.5" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="license_plate" value="Plaque d'Immatriculation" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput id="license_plate" type="text" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all uppercase" v-model="form.license_plate" required placeholder="AA-000-BB" />
                            <InputError class="mt-1.5" :message="form.errors.license_plate" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel for="password" value="Mot de passe" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput id="password" type="password" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all" v-model="form.password" required placeholder="••••••••" />
                            <InputError class="mt-1.5" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel for="password_confirmation" value="Confirmer le mot de passe" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <TextInput id="password_confirmation" type="password" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500/20 transition-all" v-model="form.password_confirmation" required placeholder="••••••••" />
                            <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-4">
                        <Link :href="route('attendant.drivers.index')" class="px-5 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200">
                            Annuler
                        </Link>
                        <PrimaryButton class="text-white px-7 py-2.5 rounded-xl font-bold shadow-md transition-all duration-200 hover:-translate-y-0.5 active:scale-95" :class="{ 'opacity-50 cursor-not-allowed': form.processing }" :disabled="form.processing">
                            <span v-if="form.processing">Enregistrement...</span>
                            <span v-else>Créer le Compte Conducteur</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
