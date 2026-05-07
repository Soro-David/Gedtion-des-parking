<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import CancelButton from '@/Components/CancelButton.vue';

const form = useForm({
    name: '',
    first_name: '',
    email: '',
    address: '',
});

function submit() {
    form.post(route('superadmin.admins.store'));
}
</script>

<template>
    <Head title="Créer un Admin" />

    <AuthenticatedLayout>
        <template #header>Nouveau Administrateur</template>

        <div class="max-w-2xl mx-auto pb-20">
            <div class="flex items-center gap-3 mb-8">
                <Link :href="route('superadmin.admins.index')" class="p-2 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200" title="Retour">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tightest">Nouveau Administrateur</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Renseignez les informations du nouvel administrateur.</p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <form @submit.prevent="submit" class="p-8 space-y-6">

                    <!-- Info invitation -->
                    <div class="flex items-start gap-3 px-4 py-3 bg-blue-50 border border-blue-100 rounded-2xl text-sm text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Un email d'invitation sera envoyé automatiquement avec un lien pour définir le mot de passe (valable 48h).
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel for="name" value="Nom" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="first_name" value="Prénom" />
                            <TextInput id="first_name" v-model="form.first_name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.first_name" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="address" value="Commune" />
                            <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" placeholder="Ex: Cocody, Yopougon, Plateau..." />
                            <InputError :message="form.errors.address" />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-4">
                        <CancelButton :href="route('superadmin.admins.index')" />
                        <PrimaryButton class="px-10 py-4 rounded-[2rem] font-black text-sm" :disabled="form.processing">
                            Enregistrer l'Admin
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
