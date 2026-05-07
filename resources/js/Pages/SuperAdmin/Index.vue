<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({});
</script>

<template>
    <Head title="Super Admin" />

    <AuthenticatedLayout>
        <template #header>Super Admin</template>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm">
                <h2 class="text-xl font-bold">Espace Super Admin</h2>
                <p class="text-sm text-gray-500 mt-2">Gérez les Admins, superviseurs et la hiérarchie des utilisateurs depuis ici.</p>

                <div class="mt-4 flex gap-3">
                    <Link :href="route('superadmin.admins.index')" class="px-4 py-2 bg-[#487119] text-white rounded-xl">Gérer les Admins</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
