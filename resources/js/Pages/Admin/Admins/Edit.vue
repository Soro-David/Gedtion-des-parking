<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ admin: Object });
</script>

<template>
    <Head title="Modifier Admin" />

    <AuthenticatedLayout>
        <template #header>Modifier Admin</template>

        <div class="bg-white p-6 rounded-2xl shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Modifier {{ admin.name }}</h3>
            <p class="text-sm text-gray-400">Formulaire d'édition non implémenté — utilisez la logique serveur existante.</p>
        </div>
    </AuthenticatedLayout>
</template>
