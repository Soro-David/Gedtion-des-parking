<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    parkings: { type: Array, default: () => [] },
});

const searchParking = ref('');

const filteredParkings = computed(() =>
    props.parkings.filter(p =>
        !searchParking.value ||
        p.name?.toLowerCase().includes(searchParking.value.toLowerCase()) ||
        p.address?.toLowerCase().includes(searchParking.value.toLowerCase())
    )
);

const totalCapacity  = computed(() => props.parkings.reduce((s, p) => s + (p.capacity || 0), 0));
const totalOccupied  = computed(() => props.parkings.reduce((s, p) => s + (p.occupied_spots || 0), 0));
const totalAvailable = computed(() => props.parkings.reduce((s, p) => s + (p.available_spots || 0), 0));
const activeCount    = computed(() => props.parkings.filter(p => p.is_active).length);
</script>

<template>
    <Head title="Parkings" />

    <AuthenticatedLayout>
        <template #header>Parkings</template>

        <!-- Stats Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
                <p class="text-2xl font-extrabold text-gray-900">{{ parkings.length }}</p>
                <p class="text-xs font-medium text-gray-500 mt-1">Parkings total</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
                <p class="text-2xl font-extrabold text-emerald-600">{{ activeCount }}</p>
                <p class="text-xs font-medium text-gray-500 mt-1">Actifs</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
                <p class="text-2xl font-extrabold text-emerald-700">{{ totalAvailable }}</p>
                <p class="text-xs font-medium text-gray-500 mt-1">Places disponibles</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
                <p class="text-2xl font-extrabold text-gray-800">{{ totalCapacity }}</p>
                <p class="text-xs font-medium text-gray-500 mt-1">Capacité totale</p>
            </div>
        </div>

        <!-- Search -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-gray-500">{{ filteredParkings.length }} parking(s) affiché(s)</p>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    v-model="searchParking"
                    type="text"
                    placeholder="Nom, adresse..."
                    class="pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#487119]/30 focus:border-[#487119] w-64"
                />
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div
                v-for="p in filteredParkings"
                :key="p.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
            >
                <!-- Card Header -->
                <div class="flex items-start justify-between p-5 pb-3">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="p-2.5 rounded-xl bg-emerald-50 flex-shrink-0">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate">{{ p.name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ p.address || 'Adresse non renseignée' }}</p>
                        </div>
                    </div>
                    <span :class="['flex-shrink-0 text-xs font-bold px-2 py-1 rounded-full border ml-2', p.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200']">
                        {{ p.is_active ? 'Actif' : 'Fermé' }}
                    </span>
                </div>

                <!-- Occupation bar -->
                <div class="px-5 pb-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                        <span>Occupation</span>
                        <span class="font-semibold">{{ p.occupied_spots }} / {{ p.capacity }}</span>
                    </div>
                    <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            :style="{ width: p.capacity > 0 ? ((p.occupied_spots / p.capacity) * 100) + '%' : '0%' }"
                            :class="[
                                'h-full rounded-full transition-all duration-500',
                                p.capacity > 0 && (p.occupied_spots / p.capacity) > 0.9 ? 'bg-red-500' :
                                p.capacity > 0 && (p.occupied_spots / p.capacity) > 0.6 ? 'bg-amber-500' : 'bg-emerald-500'
                            ]"
                        ></div>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100">
                    <div class="px-5 py-4 text-center">
                        <p class="text-xs text-gray-500 mb-0.5">Disponibles</p>
                        <p :class="[
                            'text-2xl font-extrabold',
                            p.available_spots <= 0 ? 'text-red-600' :
                            p.available_spots <= p.capacity * 0.4 ? 'text-amber-600' : 'text-emerald-600'
                        ]">{{ p.available_spots }}</p>
                    </div>
                    <div class="px-5 py-4 text-center">
                        <p class="text-xs text-gray-500 mb-0.5">Capacité</p>
                        <p class="text-2xl font-extrabold text-gray-800">{{ p.capacity }}</p>
                    </div>
                </div>

                <!-- Availability badge -->
                <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                    <span :class="[
                        'text-xs font-bold px-3 py-1.5 rounded-full',
                        p.available_spots <= 0 ? 'bg-red-100 text-red-700' :
                        p.available_spots <= p.capacity * 0.4 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'
                    ]">
                        {{ p.available_spots <= 0 ? 'Complet' : p.available_spots <= p.capacity * 0.4 ? 'Presque plein' : 'Places disponibles' }}
                    </span>

                    <!-- GPS Link -->
                    <a
                        v-if="p.latitude && p.longitude"
                        :href="`https://www.google.com/maps?q=${p.latitude},${p.longitude}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 text-xs font-semibold text-[#487119] hover:text-[#3d5e15] transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Voir sur Maps
                    </a>
                    <span v-else class="text-xs text-gray-300 italic">GPS non renseigné</span>
                </div>
            </div>

            <div v-if="filteredParkings.length === 0" class="col-span-full py-24 text-center text-gray-400">
                Aucun parking trouvé.
            </div>
        </div>

    </AuthenticatedLayout>
</template>
