<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryLinkButton from '@/Components/PrimaryLinkButton.vue';

defineProps({ admins: Array });

// delete form for admins
const del = useForm();
function confirmDelete(id){
    if(!confirm('Supprimer cet admin ?')) return;
    del.delete(route('superadmin.admins.destroy', id));
}
</script>

<template>
    <Head title="Gérer les Admins" />

    <AuthenticatedLayout>
        <template #header>Gérer les Admins</template>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold">Liste des Admins</h2>
                <p class="text-sm text-gray-500 mt-1">Les administrateurs créés par le Super Admin.</p>
            </div>
            <PrimaryLinkButton
                :href="route('superadmin.admins.create')"
                label="Ajouter un Admin"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#487119] hover:bg-[#3d5e15] text-white text-sm font-semibold rounded-xl shadow-md shadow-green-900/10 transition-all duration-200 hover:-translate-y-0.5 active:scale-95 whitespace-nowrap"
            />

        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nom Admin</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="admin in admins" :key="admin.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center font-bold text-lg border border-green-100">
                                        {{ admin.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900">{{ admin.name }}</div>
                                        <div class="text-xs text-gray-400">ID: #{{ admin.id.toString().padStart(4,'0') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ admin.email }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a :href="route('superadmin.admins.edit', admin.id)" class="text-sm font-semibold text-gray-500 hover:text-green-700"><font-awesome-icon icon="fa-solid fa-pen-to-square" /></a>
                                    <button @click="() => confirmDelete(admin.id)" class="text-sm font-semibold text-red-500 hover:text-red-700"><font-awesome-icon icon="fa-solid fa-trash" /></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="admins.length === 0">
                            <td colspan="3" class="px-6 py-16 text-center text-gray-400">
                                Aucun admin trouvé.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
