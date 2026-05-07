<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryLinkButton from '@/Components/PrimaryLinkButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    supervisors: Array,
});

const PER_PAGE = 10;
const currentPage     = ref(1);
const totalPages      = computed(() => Math.max(1, Math.ceil((props.supervisors?.length ?? 0) / PER_PAGE)));
const pagedSupervisors = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return (props.supervisors ?? []).slice(start, start + PER_PAGE);
});

const confirmDelete = (id) => {
    if (confirm('Voulez-vous vraiment supprimer ce superviseur ?')) {
        router.delete(route('admin.superviseur.destroy', id));
    }
};

const resendInvitation = (id) => {
    if (confirm('Renvoyer l\'email d\'invitation à ce superviseur ?')) {
        router.post(route('admin.superviseur.resend-invitation', id));
    }
};
</script>

<template>
    <Head title="Gestion des Superviseurs" />

    <AuthenticatedLayout>
        <template #header>Superviseurs</template>

        <!-- En-tête de page avec boutons -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Gestion des Superviseurs</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ supervisors.length }} superviseur{{ supervisors.length > 1 ? 's' : '' }} enregistré{{ supervisors.length > 1 ? 's' : '' }}
                </p>
            </div>
            
            <PrimaryLinkButton
                :href="route('admin.superviseur.create')"
                label="Ajouter un Superviseur"
                icon="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
            />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Superviseur</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Inscrit par</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="supervisor in pagedSupervisors" :key="supervisor.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-[#4A1725]/10 text-[#4A1725] flex items-center justify-center font-bold text-lg border border-[#4A1725]/10">
                                        {{ supervisor.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 group-hover:text-[#4A1725] transition-colors">
                                            {{ supervisor.name || 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-400">ID: #{{ supervisor.id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ supervisor.email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ supervisor.supervisor?.creator ? supervisor.supervisor.creator.name : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(supervisor.created_at).toLocaleDateString('fr-FR') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Show -->
                                    <Link
                                        :href="route('admin.superviseur.show', supervisor.id)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200"
                                        title="Voir le détail"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <!-- Edit -->
                                    <Link
                                        :href="route('admin.superviseur.edit', supervisor.id)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-all duration-200"
                                        title="Modifier"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <!-- Resend invitation -->
                                    <button
                                        v-if="!supervisor.has_password"
                                        @click="resendInvitation(supervisor.id)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-all duration-200"
                                        title="Renvoyer l'invitation"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <!-- Delete -->
                                    <button
                                        @click="confirmDelete(supervisor.id)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200"
                                        title="Supprimer"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="supervisors.length === 0">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="text-lg font-medium">Aucun superviseur enregistré.</p>
                                    <p class="text-sm mt-1">Cliquez sur « Ajouter un Superviseur » pour commencer.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :current-page="currentPage"
                :total-pages="totalPages"
                :total-items="supervisors.length"
                class="px-4"
                @page-change="currentPage = $event"
            />
        </div>
    </AuthenticatedLayout>
</template>
