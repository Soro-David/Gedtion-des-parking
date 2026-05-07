<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryLinkButton from '@/Components/PrimaryLinkButton.vue'; 


defineProps({ admins: Array });
</script>

<template>
    <Head title="Gestion des Admins" />

    <AuthenticatedLayout>
        <template #header>Admins</template>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold">Liste des Admins</h2>
                <p class="text-sm text-gray-500 mt-1">Gestion des comptes administrateurs.</p>
            </div>
            <PrimaryLinkButton :href="route('admin.admins.create')" label="Ajouter un Admin" />
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Admin</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Créé le</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in admins" :key="user.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center font-bold text-lg border border-green-100">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900">{{ user.name }}</div>
                                        <div class="text-xs text-gray-400">ID: #{{ user.id.toString().padStart(4,'0') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(user.created_at).toLocaleDateString('fr-FR') }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('admin.admins.edit', user.id)" class="text-sm font-semibold text-gray-500 hover:text-green-700">Modifier</Link>
                            </td>
                        </tr>
                        <tr v-if="admins.length === 0">
                            <td colspan="4" class="px-6 py-16 text-center text-gray-400">
                                Aucun admin trouvé.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
