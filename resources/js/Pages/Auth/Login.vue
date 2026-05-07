<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Bienvenue</h1>
            <p class="text-gray-500 mt-2 font-medium">Connectez-vous à votre espace <span class="text-indigo-600">Plateau Parck</span></p>
        </div>

        <div v-if="status" class="mb-6 p-4 rounded-2xl bg-green-50 text-sm font-semibold text-green-600 border border-green-100">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Email" class="text-gray-700 font-semibold mb-1.5 ml-1" />
                <div class="relative group">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                        </svg>
                    </span>
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full py-3.5 pl-12 bg-gray-50 border-gray-100 focus:bg-white focus:ring-4 focus:ring-indigo-100 rounded-2xl transition-all"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="exemple@parking.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex justify-between mb-1.5 ml-1">
                    <InputLabel for="password" value="Mot de passe" class="text-gray-700 font-semibold" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
                    >
                        Oublié ?
                    </Link>
                </div>
                <div class="relative group">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full py-3.5 pl-12 bg-gray-50 border-gray-100 focus:bg-white focus:ring-4 focus:ring-indigo-100 rounded-2xl transition-all"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer group">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="ms-3 text-sm text-gray-500 font-medium group-hover:text-gray-700 transition-colors">Rester connecté</span>
                </label>
            </div>

            <div>
                <PrimaryButton
                    class="w-full justify-center py-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 rounded-2xl shadow-lg shadow-indigo-200 text-base font-bold transition-all transform hover:-translate-y-0.5"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Se connecter
                </PrimaryButton>
            </div>

            <div v-if="!canResetPassword" class="text-center">
                <p class="text-xs text-gray-500 italic">Contactez l'administrateur si vous avez perdu vos accès.</p>
            </div>
        </form>
    </GuestLayout>
</template>
