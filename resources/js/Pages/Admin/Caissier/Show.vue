<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    caissier: Object,
});
</script>

<template>
    <Head title="Détail Caissier" />

    <AuthenticatedLayout>
        <template #header>Détail du Caissier</template>

        <div class="max-w-3xl mx-auto pb-20">
            <div class="flex items-center gap-3 mb-8">
                <Link :href="route('admin.caissier.index')" class="p-2 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200" title="Retour">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-3xl font-black text-gray-900">{{ caissier.name }} {{ caissier.first_name }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">ID : #{{ caissier.id.toString().padStart(4, '0') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden p-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nom</p>
                        <p class="text-gray-900 font-medium">{{ caissier.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Prénom</p>
                        <p class="text-gray-900 font-medium">{{ caissier.first_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email</p>
                        <p class="text-gray-900 font-medium">{{ caissier.email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Téléphone</p>
                        <p class="text-gray-900 font-medium">{{ caissier.phone ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Adresse</p>
                        <p class="text-gray-900 font-medium">{{ caissier.address ?? '—' }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Contact d'urgence</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Nom</p>
                            <p class="text-gray-900 font-medium">{{ caissier.emergency_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Téléphone</p>
                            <p class="text-gray-900 font-medium">{{ caissier.emergency_contact ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Relation</p>
                            <p class="text-gray-900 font-medium">{{ caissier.emergency_relationship ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end">
                    <Link
                        :href="route('admin.caissier.edit', caissier.id)"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#487119] hover:bg-[#3d5e15] text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
