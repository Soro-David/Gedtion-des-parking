<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    parkings: Array,
    role: String,
});

const flash = computed(() => usePage().props.flash ?? {});

const PER_PAGE    = 10;
const currentPage = ref(1);

const totalPages    = computed(() => Math.max(1, Math.ceil(props.parkings.length / PER_PAGE)));
const pagedParkings = computed(() =>
    props.parkings.slice((currentPage.value - 1) * PER_PAGE, currentPage.value * PER_PAGE)
);
</script>

<template>
    <Head title="Tarifs de stationnement" />

    <AuthenticatedLayout>
        <template #header>Tarifs de stationnement</template>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-6 px-5 py-3 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Parkings enregistrés</h3>
                <p class="text-sm text-gray-500 mt-0.5">Cliquez sur « Voir les tarifs » pour gérer la grille tarifaire de chaque parking.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Référence</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Nom du parking</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Tarif de base</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Créneaux tarifaires</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="parking in pagedParkings" :key="parking.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="text-[10px] font-black tracking-widest text-[#4A1725] uppercase">{{ parking.reference }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-gray-900">{{ parking.name }}</span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span v-if="parking.price !== null">
                                    <span class="font-bold text-gray-900">{{ Number(parking.price).toLocaleString('fr-FR') }}</span>
                                    <span class="text-xs text-gray-500 ml-1">FCFA</span>
                                </span>
                                <span v-else class="text-gray-400 text-sm italic">Non défini</span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span v-if="parking.rates_count > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ parking.rates_count }} créneau{{ parking.rates_count > 1 ? 'x' : '' }}
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-400 border border-gray-200">
                                    Aucun créneau
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <Link
                                    :href="route('parking-rates.show', parking.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-50 text-green-700 font-semibold text-xs hover:bg-green-100 transition-colors border border-green-200"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Voir les tarifs
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="parkings.length === 0">
                            <td colspan="5" class="px-5 py-16 text-center text-gray-400 italic">Aucun parking enregistré.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-4 border-t border-gray-100">
                <Pagination
                    :currentPage="currentPage"
                    :totalPages="totalPages"
                    :totalItems="parkings.length"
                    :perPage="PER_PAGE"
                    @page-change="currentPage = $event"
                />
            </div>
        </div>

    </AuthenticatedLayout>
</template>
