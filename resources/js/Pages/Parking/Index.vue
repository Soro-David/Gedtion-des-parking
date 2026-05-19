<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PrimaryLinkButton from '@/Components/PrimaryLinkButton.vue';
import ParkingsMapView from '@/Components/ParkingsMapView.vue';
import { ref } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh';

defineProps({
    parkings: Array,
});

const form = useForm({});
const viewMode = ref('grid'); // 'grid' ou 'map'

useAutoRefresh();

const deleteParking = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce parking ?')) {
        form.delete(route('parkings.destroy', id));
    }
};

const toggleStatus = (id) => {
    form.patch(route('parkings.toggleStatus', id));
};

const formatRate = (rate) => {
    const from = rate.from_time ? rate.from_time.substring(0, 5) : '—';
    const to   = rate.to_time   ? rate.to_time.substring(0, 5)   : '—';
    return `${from} – ${to}`;
};

const formatAmount = (amount) => {
    const n = parseFloat(amount);
    return (n % 1 === 0 ? parseInt(n) : n).toLocaleString('fr-FR');
};
</script>

<template>
    <Head title="Gestion des Parkings" />

    <AuthenticatedLayout>
        <template #header>Parkings</template>

        <div class="flex flex-col gap-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Liste des Parkings</h2>
                    <p class="text-sm text-gray-500 mt-1">Gérez vos emplacements de parking et leurs capacités.</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Toggle grille / carte -->
                    <div class="flex items-center bg-gray-100 rounded-xl p-1">
                        <button
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white shadow text-gray-800' : 'text-gray-400 hover:text-gray-600'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            Grille
                        </button>
                        <button
                            @click="viewMode = 'map'"
                            :class="viewMode === 'map' ? 'bg-white shadow text-gray-800' : 'text-gray-400 hover:text-gray-600'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            Carte
                        </button>
                    </div>
                    <PrimaryLinkButton :href="route('parkings.create')" label="+ Nouveau Parking" />
                </div>
            </div>

            <!-- Vue Carte Google Maps -->
            <div v-if="viewMode === 'map'" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden p-4">
                <ParkingsMapView :parkings="parkings" height="520px" />
            </div>

            <!-- Vue Tableau -->
            <div v-if="viewMode === 'grid'" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/70">
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Référence</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Nom</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Adresse</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Occupation</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Tarifs</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Statut</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Créé par</th>
                                <th class="text-right px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="parking in parkings"
                                :key="parking.id"
                                class="hover:bg-gray-50/60 transition-colors group"
                            >
                                <!-- Référence + image -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                                            <img v-if="parking.image" :src="'/storage/' + parking.image" class="w-full h-full object-cover" alt="">
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-black tracking-widest text-[#4A1725] uppercase">{{ parking.reference }}</span>
                                    </div>
                                </td>

                                <!-- Nom -->
                                <td class="px-5 py-4">
                                    <span class="font-bold text-gray-900">{{ parking.name }}</span>
                                </td>

                                <!-- Adresse -->
                                <td class="px-5 py-4">
                                    <span class="text-gray-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        {{ parking.address }}
                                    </span>
                                </td>

                                <!-- Occupation -->
                                <td class="px-5 py-4">
                                    <div class="min-w-[120px]">
                                        <div class="flex justify-between text-[10px] font-bold mb-1">
                                            <span class="text-red-500">{{ parking.active_sessions_count }} occupé{{ parking.active_sessions_count > 1 ? 's' : '' }}</span>
                                            <span class="text-green-600">{{ Math.max(0, parking.capacity - parking.active_sessions_count) }} libre{{ Math.max(0, parking.capacity - parking.active_sessions_count) > 1 ? 's' : '' }}</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-red-400 rounded-full transition-all duration-500"
                                                :style="{ width: parking.capacity > 0 ? (parking.active_sessions_count / parking.capacity * 100) + '%' : '0%' }"
                                            ></div>
                                        </div>
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ parking.capacity }} places</div>
                                    </div>
                                </td>

                                <!-- Tarifs -->
                                <td class="px-5 py-4">
                                    <div v-if="parking.rates && parking.rates.length > 0" class="flex flex-col gap-1">
                                        <div
                                            v-for="rate in parking.rates"
                                            :key="rate.id"
                                            class="flex items-center gap-2 px-2 py-1 bg-blue-50 rounded-lg"
                                        >
                                            <span class="text-[10px] text-blue-700">{{ formatRate(rate) }}</span>
                                            <span class="text-[10px] font-black text-blue-900 ml-auto">{{ formatAmount(rate.amount) }} F</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>

                                <!-- Statut -->
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-widest uppercase"
                                        :class="parking.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                    >
                                        {{ parking.is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>

                                <!-- Créé par -->
                                <td class="px-5 py-4">
                                    <span class="font-medium text-gray-700 text-xs">{{ parking.creator?.name }}</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="route('parkings.edit', parking.id)"
                                            class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-600 font-bold text-xs hover:bg-gray-100 transition-colors"
                                        >
                                            Modifier
                                        </Link>
                                        <button
                                            @click="toggleStatus(parking.id)"
                                            :title="parking.is_active ? 'Désactiver' : 'Activer'"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors"
                                            :class="parking.is_active ? 'bg-amber-50 text-amber-500 hover:bg-amber-100' : 'bg-green-50 text-green-500 hover:bg-green-100'"
                                        >
                                            <svg v-if="parking.is_active" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                        <button
                                            @click="deleteParking(parking.id)"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div v-if="parkings.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-[3rem] border border-dashed border-gray-200">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </div>
                <p class="text-gray-400 font-medium">Aucun parking enregistré pour le moment.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
