<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Lien expiré" />

        <div class="text-center">
            <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-500 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-gray-900 mb-2">Lien invalide ou expiré</h2>
            <p class="text-sm text-gray-500">
                Ce lien d'invitation est invalide ou a expiré.<br>
                Veuillez contacter votre administrateur pour recevoir un nouveau lien.
            </p>
        </div>
    </GuestLayout>
</template>
