<script setup>
import PrimaryLinkButton from '@/Components/PrimaryLinkButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    drivers: Array,
});
</script>

<template>
    <Head title="Espace Conducteurs" />

    <AuthenticatedLayout>
        <template #header>Conducteurs</template>

        <!-- En-tête de page -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Espace Conducteurs</h2>
                <p class="text-sm text-gray-500 mt-1">Gérez les conducteurs et leurs immatriculations.</p>
            </div>
            <PrimaryLinkButton :href="route('attendant.drivers.create')" label="Ajouter un Conducteur" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Conducteur</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Immatriculation</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in drivers" :key="user.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center font-bold text-lg border border-orange-100">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 group-hover:text-orange-700 transition-colors">{{ user.name }}</div>
                                        <div class="text-xs text-gray-400">Inscrit par: {{ user.driver?.creator?.name || 'Système' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-gray-100 text-gray-800 border border-gray-200 uppercase tracking-widest shadow-sm">
                                    {{ user.driver?.license_plate || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ user.email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link :href="route('attendant.drivers.show', user.id)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-400 hover:text-orange-700 transition-colors">
                                    Gérer
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="drivers.length === 0">
                            <td colspan="4" class="px-6 py-16 text-center text-gray-400 italic">Aucun conducteur enregistré.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
