<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, required: true },
    name:  { type: String, required: true },
});

const form = useForm({
    token:                 props.token,
    password:              '',
    password_confirmation: '',
    avatar:                null,
});

const avatarPreview = ref(null);

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    } else {
        form.avatar = null;
        avatarPreview.value = null;
    }
};

const submit = () => {
    form.post(route('invitation.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Définir mon mot de passe" />

        <div class="mb-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-gray-900">Bienvenue, {{ name }} !</h2>
            <p class="text-sm text-gray-500 mt-1">
                Définissez votre mot de passe pour activer votre compte <span class="font-medium text-gray-700">{{ email }}</span>.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" value="Nouveau mot de passe" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-1" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-1" />
            </div>

            <div>
                <InputLabel for="avatar" value="Photo de profil (Optionnelle)" />
                <div class="mt-2 flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                        <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input
                        id="avatar"
                        type="file"
                        class="block w-full text-sm text-gray-500
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-xl file:border-0
                               file:text-sm file:font-semibold
                               file:bg-green-50 file:text-green-700
                               hover:file:bg-green-100 cursor-pointer"
                        accept="image/*"
                        @change="onFileChange"
                    />
                </div>
                <InputError :message="form.errors.avatar" class="mt-1" />
            </div>

            <InputError :message="form.errors.token" />

            <PrimaryButton class="w-full justify-center py-3 rounded-xl font-black" :disabled="form.processing">
                Activer mon compte
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
