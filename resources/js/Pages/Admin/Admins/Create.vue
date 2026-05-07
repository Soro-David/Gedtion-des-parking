<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({});
</script>

<template>
    <Head title="Créer un Admin" />

    <AuthenticatedLayout>
        <template #header>Créer Admin</template>

        <div class="bg-white p-6 rounded-2xl shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Formulaire de création d'admin</h3>
            <!-- Le formulaire peut être complété plus tard -->
            <p class="text-sm text-gray-400">Formulaire non implémenté — utilise la logique côté serveur existante.</p>
        </div>
    </AuthenticatedLayout>
</template>
