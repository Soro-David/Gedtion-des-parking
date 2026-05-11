<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    rates: Array,
    parkings: Array,
    role: String,
});

const flash = computed(() => usePage().props.flash ?? {});

// ---------- Création ----------
const form = useForm({
    parking_id:   '',
    label:        '',
    from_minutes: 0,
    to_minutes:   '',
    amount:       '',
});

const submit = () => {
    form.post(route('parking-rates.store'), {
        onSuccess: () => form.reset(),
    });
};

// ---------- Suppression ----------
const deleting = ref(null);
const confirmDelete = (rate) => { deleting.value = rate; };
const cancelDelete = () => { deleting.value = null; };
const doDelete = () => {
    useForm({}).delete(route('parking-rates.destroy', deleting.value.id), {
        onSuccess: () => { deleting.value = null; },
    });
};

// ---------- Modification ----------
const editing = ref(null);
const editForm = useForm({
    parking_id:   '',
    label:        '',
    from_minutes: 0,
    to_minutes:   '',
    amount:       '',
});

const startEdit = (rate) => {
    editing.value = rate;
    editForm.parking_id   = rate.parking_id ?? '';
    editForm.label        = rate.label ?? '';
    editForm.from_minutes = rate.from_minutes;
    editForm.to_minutes   = rate.to_minutes ?? '';
    editForm.amount       = rate.amount;
};
const cancelEdit = () => { editing.value = null; };
const saveEdit = () => {
    editForm.put(route('parking-rates.update', editing.value.id), {
        onSuccess: () => { editing.value = null; },
    });
};

// ---------- Helpers ----------
const formatDuration = (from, to) => {
    const label = (m) => m < 60 ? `${m} min` : `${Math.floor(m / 60)}h${m % 60 ? (m % 60) + 'min' : ''}`;
    return to ? `${label(from)} – ${label(to)}` : `À partir de ${label(from)}`;
};
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulaire d'ajout -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-5">Ajouter un tarif</h3>

                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Parking (optionnel = global) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Parking <span class="text-gray-400 font-normal">(laisser vide = global)</span></label>
                            <select v-model="form.parking_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none bg-white">
                                <option value="">— Tous les parkings (global) —</option>
                                <option v-for="p in parkings" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="form.errors.parking_id" class="mt-1 text-xs text-red-600">{{ form.errors.parking_id }}</p>
                        </div>

                        <!-- Libellé -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Libellé <span class="text-gray-400 font-normal">(optionnel)</span></label>
                            <input v-model="form.label" type="text" placeholder="ex: Première heure" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" />
                        </div>

                        <!-- De (minutes) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">De (min)</label>
                                <input v-model.number="form.from_minutes" type="number" min="0" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" />
                                <p v-if="form.errors.from_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.from_minutes }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">À (min) <span class="text-gray-400 font-normal">optionnel</span></label>
                                <input v-model="form.to_minutes" type="number" min="1" placeholder="∞" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" />
                                <p v-if="form.errors.to_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.to_minutes }}</p>
                            </div>
                        </div>

                        <!-- Montant -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Montant (FCFA)</label>
                            <input v-model.number="form.amount" type="number" min="0" step="50" placeholder="ex: 500" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <PrimaryButton type="submit" :disabled="form.processing">
                            Ajouter le tarif
                        </PrimaryButton>
                    </form>
                </div>
            </div>

            <!-- Liste des tarifs -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">Grille tarifaire</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Les tarifs spécifiques à un parking ont la priorité sur les tarifs globaux.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Parking</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Libellé</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Intervalle</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Montant</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="rate in rates" :key="rate.id" class="hover:bg-gray-50/50 transition-colors">

                                    <!-- Ligne normale -->
                                    <template v-if="editing?.id !== rate.id">
                                        <td class="px-5 py-4 whitespace-nowrap text-sm">
                                            <span v-if="rate.parking_name" class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ rate.parking_name }}
                                            </span>
                                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                                                Global
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-700">{{ rate.label || '—' }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ formatDuration(rate.from_minutes, rate.to_minutes) }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="font-bold text-gray-900">{{ rate.amount.toLocaleString('fr-FR') }}</span>
                                            <span class="text-xs text-gray-500 ml-1">FCFA</span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <button @click="startEdit(rate)" class="text-sm font-semibold text-gray-400 hover:text-blue-600 transition-colors"> <font-awesome-icon icon="fa-solid fa-pen-to-square" /></button>
                                                <button @click="confirmDelete(rate)" class="text-sm font-semibold text-gray-400 hover:text-red-600 transition-colors"> <font-awesome-icon icon="fa-solid fa-trash" /></button>
                                            </div>
                                        </td>
                                    </template>

                                    <!-- Ligne d'édition -->
                                    <template v-else>
                                        <td class="px-5 py-3">
                                            <select v-model="editForm.parking_id" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-full focus:ring-2 focus:ring-green-500 outline-none bg-white">
                                                <option value="">Global</option>
                                                <option v-for="p in parkings" :key="p.id" :value="p.id">{{ p.name }}</option>
                                            </select>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input v-model="editForm.label" type="text" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-full focus:ring-2 focus:ring-green-500 outline-none" />
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-1">
                                                <input v-model.number="editForm.from_minutes" type="number" min="0" :class="['border rounded-lg px-2 py-1.5 text-sm w-16 focus:ring-2 focus:ring-green-500 outline-none', editForm.errors.from_minutes ? 'border-red-400' : 'border-gray-200']" />
                                                <span class="text-gray-400 text-xs">–</span>
                                                <input v-model="editForm.to_minutes" type="number" min="1" placeholder="∞" :class="['border rounded-lg px-2 py-1.5 text-sm w-16 focus:ring-2 focus:ring-green-500 outline-none', editForm.errors.from_minutes ? 'border-red-400' : 'border-gray-200']" />
                                            </div>
                                            <p v-if="editForm.errors.from_minutes" class="mt-1 text-xs text-red-600">{{ editForm.errors.from_minutes }}</p>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input v-model.number="editForm.amount" type="number" min="0" step="50" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-24 focus:ring-2 focus:ring-green-500 outline-none" />
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="saveEdit" :disabled="editForm.processing" class="text-sm font-semibold text-green-700 hover:text-green-900 transition-colors">Sauvegarder</button>
                                                <button @click="cancelEdit" class="text-sm font-semibold text-gray-400 hover:text-gray-600 transition-colors">Annuler</button>
                                            </div>
                                        </td>
                                    </template>

                                </tr>
                                <tr v-if="rates.length === 0">
                                    <td colspan="5" class="px-5 py-16 text-center text-gray-400 italic">Aucun tarif configuré. Ajoutez votre premier tarif ci-contre.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de suppression -->
        <Teleport to="body">
            <div v-if="deleting" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md mx-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Supprimer ce tarif ?</h3>
                    <p class="text-gray-500 text-sm mb-8">Cette action est irréversible. Le tarif sera définitivement supprimé.</p>
                    <div class="flex gap-3">
                        <button @click="cancelDelete" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Annuler</button>
                        <button @click="doDelete" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold transition-colors">Supprimer</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
