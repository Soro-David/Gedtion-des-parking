<script setup>
import PrimaryLinkButton from '@/Components/PrimaryLinkButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    attendants: Array,
});

const PER_PAGE = 10;
const currentPage    = ref(1);
const totalPages     = computed(() => Math.max(1, Math.ceil((props.attendants?.length ?? 0) / PER_PAGE)));
const pagedAttendants = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return (props.attendants ?? []).slice(start, start + PER_PAGE);
});
</script>

<template>
    <Head title="Mes Agents" />

    <AuthenticatedLayout>
        <template #header>Agents</template>

        <!-- En-tête de page -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Gestion des Agents</h2>
                <p class="text-sm text-gray-500 mt-1">Liste des agents sous votre supervision.</p>
            </div>
            <PrimaryLinkButton
                :href="route('supervisor.attendants.create')"
                label="Ajouter un Agent"
            />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Agent</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in pagedAttendants" :key="user.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center font-bold text-lg border border-green-100">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 group-hover:text-green-700 transition-colors">{{ user.name }}</div>
                                        <div class="text-xs text-gray-400">ID: #{{ user.id.toString().padStart(4, '0') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ user.email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(user.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link :href="route('supervisor.attendants.edit', user.id)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-400 hover:text-green-700 transition-colors">
                                    <font-awesome-icon icon="fa-solid fa-pen-to-square" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="attendants.length === 0">
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="text-lg font-medium">Aucun agent trouvé.</p>
                                    <p class="text-sm mt-1">Commencez par en ajouter un nouveau.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :current-page="currentPage"
                :total-pages="totalPages"
                :total-items="attendants.length"
                class="px-4"
                @page-change="currentPage = $event"
            />
        </div>
    </AuthenticatedLayout>
</template>
