<script setup>
defineProps({
    stats: { type: Object, default: () => ({}) },
});

const vehicles = [
    { plate: 'AB-123-CD', model: 'Toyota Corolla', status: 'Garé', location: 'Parking Central' },
    { plate: 'EE-456-FF', model: 'Mercedes C200', status: 'En circulation', location: '-' },
];
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Panneau principal -->
        <div class="md:col-span-8">
            <!-- Carte Solde -->
            <div class="bg-gradient-to-br from-[#4A1725] to-[#7E1B1C] rounded-3xl p-8 text-white mb-8 shadow-xl relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/5"></div>
                <div class="absolute -bottom-12 -left-8 w-40 h-40 rounded-full bg-white/5"></div>
                <div class="relative z-10">
                    <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Solde Actuel</p>
                    <h2 class="text-4xl font-extrabold tracking-tight mb-8">12,500 FCFA</h2>
                    <div class="flex gap-3">
                        <button class="flex-grow bg-white text-[#4A1725] font-bold py-3 rounded-2xl hover:bg-white/90 transition-colors text-sm">
                            Recharger
                        </button>
                        <button class="px-5 border border-white/30 rounded-2xl hover:bg-white/10 transition-colors text-sm font-semibold">
                            Détails
                        </button>
                    </div>
                </div>
            </div>

            <!-- Véhicules -->
            <h3 class="text-xl font-bold text-gray-800 mb-5">Mes Véhicules</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div
                    v-for="v in vehicles"
                    :key="v.plate"
                    class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300"
                >
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2" />
                            </svg>
                        </div>
                        <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold border', v.status === 'Garé' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-gray-50 text-gray-500 border-gray-100']">
                            {{ v.status }}
                        </span>
                    </div>
                    <p class="text-lg font-extrabold text-gray-900 tracking-wider uppercase">{{ v.plate }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ v.model }}</p>
                    <div class="mt-4 pt-4 border-t border-gray-50 flex items-center gap-1.5 text-xs text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        {{ v.location }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau latéral -->
        <div class="md:col-span-4 space-y-6">
            <!-- Recherche Parking -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4">Trouver un Parking</h3>
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Rechercher une zone..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 text-sm focus:ring-2 focus:ring-[#4A1725]/30 focus:border-[#4A1725] transition-all outline-none"
                    />
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="mt-4 bg-gray-100 rounded-xl h-36 flex items-center justify-center">
                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Carte interactive — bientôt</span>
                </div>
            </div>

            <!-- Derniers Paiements -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-5">Derniers Paiements</h3>
                <div class="space-y-4">
                    <div v-for="i in 3" :key="i" class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Parking Central</p>
                            <p class="text-xs text-gray-400">Hier, 18:30</p>
                        </div>
                        <p class="text-sm font-bold text-red-500">- 500 F</p>
                    </div>
                </div>
                <button class="w-full mt-5 py-2.5 text-xs font-bold text-[#4A1725] hover:bg-[#4A1725]/5 rounded-xl transition-colors border border-[#4A1725]/20">
                    Voir l'historique complet
                </button>
            </div>
        </div>
    </div>
</template>
