<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    parking: Object,
    rates: Array,
    role: String,
});

const flash = computed(() => usePage().props.flash ?? {});

// ---------- Création ----------
const form = useForm({
    parking_id: props.parking.id,
    label:      '',
    from_time:  '',
    to_time:    '',
    amount:     '',
});

const submit = () => {
    form.post(route('parking-rates.store'), {
        onSuccess: () => {
            form.label     = '';
            form.from_time = '';
            form.to_time   = '';
            form.amount    = '';
        },
    });
};

// ---------- Suppression ----------
const deleting = ref(null);
const confirmDelete = (rate) => { deleting.value = rate; };
const cancelDelete  = () => { deleting.value = null; };
const doDelete = () => {
    useForm({}).delete(route('parking-rates.destroy', deleting.value.id), {
        onSuccess: () => { deleting.value = null; },
    });
};

// ---------- Modification ----------
const editing = ref(null);
const editForm = useForm({
    label:     '',
    from_time: '',
    to_time:   '',
    amount:    '',
});

const startEdit = (rate) => {
    editing.value      = rate;
    editForm.label     = rate.label ?? '';
    editForm.from_time = rate.from_time ?? '';
    editForm.to_time   = rate.to_time ?? '';
    editForm.amount    = rate.amount;
};
const cancelEdit = () => { editing.value = null; };
const saveEdit = () => {
    editForm.put(route('parking-rates.update', editing.value.id), {
        onSuccess: () => { editing.value = null; },
    });
};

// ---------- Helpers ----------
const formatTime = (t) => t ? t.substring(0, 5) : '—';

// ---------- Pagination créneaux ----------
const RATE_PER_PAGE  = 4;
const ratePage       = ref(1);
const rateTotalPages = computed(() => Math.max(1, Math.ceil(props.rates.length / RATE_PER_PAGE)));
const pagedRates     = computed(() =>
    props.rates.slice((ratePage.value - 1) * RATE_PER_PAGE, ratePage.value * RATE_PER_PAGE)
);
</script>

<template>
    <Head :title="`Tarifs — ${parking.name}`" />

    <AuthenticatedLayout>
        <template #header>Grille tarifaire</template>

        <!-- Breadcrumb -->
        <div class="flex items-center gap-3 mb-6">
            <Link
                :href="route('parking-rates.index')"
                class="w-9 h-9 flex items-center justify-center rounded-full bg-white border border-gray-100 shadow-sm text-gray-500 hover:text-green-600 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <div>
                <p class="text-xs text-gray-400 font-medium">Tarifs de stationnement</p>
                <h2 class="text-xl font-black text-gray-900">{{ parking.name }}
                    <span class="text-xs font-normal text-gray-400 ml-2">{{ parking.reference }}</span>
                </h2>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-6 px-5 py-3 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <!-- Tarif de base -->
        <div class="mb-6 px-5 py-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">
                    Tarif de base :
                    <span v-if="parking.price !== null" class="font-black">
                        {{ Number(parking.price).toLocaleString('fr-FR') }} FCFA
                    </span>
                    <span v-else class="font-normal italic">non défini</span>
                </p>
                <p class="text-xs text-amber-600 mt-0.5">
                    Ce tarif s'applique en dehors des créneaux horaires définis ci-dessous.
                    Pour le modifier, <Link :href="route('parkings.edit', parking.id)" class="underline font-semibold hover:text-amber-900">éditez le parking</Link>.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Formulaire d'ajout -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 min-h-[435px] flex flex-col">
                <h3 class="text-lg font-bold text-gray-900 mb-5">
                    Ajouter un créneau
                </h3>

                <form @submit.prevent="submit" class="space-y-4 flex-1 flex flex-col">

                    <!-- Libellé -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Libellé <span class="text-gray-400 font-normal">(optionnel)</span>
                        </label>

                        <input
                            v-model="form.label"
                            type="text"
                            placeholder="ex: Heure de pointe"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none"
                        />
                    </div>

                    <!-- Intervalle horaire -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Heure de début
                            </label>

                            <input
                                v-model="form.from_time"
                                type="time"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none"
                                required
                            />

                            <p v-if="form.errors.from_time" class="mt-1 text-xs text-red-600">
                                {{ form.errors.from_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Heure de fin
                            </label>

                            <input
                                v-model="form.to_time"
                                type="time"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none"
                                required
                            />

                            <p v-if="form.errors.to_time" class="mt-1 text-xs text-red-600">
                                {{ form.errors.to_time }}
                            </p>
                        </div>
                    </div>

                    <!-- Montant -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Montant (FCFA)
                        </label>

                        <input
                            v-model.number="form.amount"
                            type="number"
                            min="0"
                            step="50"
                            placeholder="ex: 500"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none"
                            required
                        />

                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">
                            {{ form.errors.amount }}
                        </p>
                    </div>

                    <!-- Bouton -->
                    <div class="mt-auto pt-4">
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                            class="w-full px-4 py-2.5 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-colors"
                        >
                            Ajouter le créneau
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>

            <!-- Grille tarifaire -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">Créneaux horaires</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Le tarif du créneau actif s'applique lors de l'enregistrement d'un stationnement.</p>
                    </div>
                    <div class="overflow-x-auto min-h-[280px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Libellé</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Début</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Fin</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Montant</th>
                                    <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="rate in pagedRates" :key="rate.id" class="hover:bg-gray-50/50 transition-colors">

                                    <!-- Ligne normale -->
                                    <template v-if="editing?.id !== rate.id">
                                        <td class="px-5 py-4 text-sm text-gray-700">{{ rate.label || '—' }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ formatTime(rate.from_time) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ formatTime(rate.to_time) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="font-bold text-gray-900">{{ Number(rate.amount).toLocaleString('fr-FR') }}</span>
                                            <span class="text-xs text-gray-500 ml-1">FCFA</span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <button @click="startEdit(rate)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Modifier">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(rate)" class="text-gray-400 hover:text-red-600 transition-colors" title="Supprimer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </template>

                                    <!-- Ligne d'édition inline -->
                                    <template v-else>
                                        <td class="px-5 py-3">
                                            <input
                                                v-model="editForm.label"
                                                type="text"
                                                placeholder="Libellé"
                                                class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-full focus:ring-2 focus:ring-green-500 outline-none"
                                            />
                                        </td>
                                        <td class="px-5 py-3">
                                            <input
                                                v-model="editForm.from_time"
                                                type="time"
                                                :class="['border rounded-lg px-2 py-1.5 text-sm w-28 focus:ring-2 focus:ring-green-500 outline-none', editForm.errors.from_time ? 'border-red-400' : 'border-gray-200']"
                                            />
                                            <p v-if="editForm.errors.from_time" class="mt-1 text-xs text-red-600">{{ editForm.errors.from_time }}</p>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input
                                                v-model="editForm.to_time"
                                                type="time"
                                                :class="['border rounded-lg px-2 py-1.5 text-sm w-28 focus:ring-2 focus:ring-green-500 outline-none', editForm.errors.to_time ? 'border-red-400' : 'border-gray-200']"
                                            />
                                            <p v-if="editForm.errors.to_time" class="mt-1 text-xs text-red-600">{{ editForm.errors.to_time }}</p>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input
                                                v-model.number="editForm.amount"
                                                type="number"
                                                min="0"
                                                step="50"
                                                class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-24 focus:ring-2 focus:ring-green-500 outline-none"
                                            />
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
                                    <td colspan="5" class="px-5 py-16 text-center text-gray-400 italic">
                                        Aucun créneau horaire. Ajoutez votre premier créneau ci-contre.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        :current-page="ratePage"
                        :total-pages="rateTotalPages"
                        :total-items="rates.length"
                        :per-page="RATE_PER_PAGE"
                        class="px-4"
                        @page-change="ratePage = $event"
                    />
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
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Supprimer ce créneau ?</h3>
                    <p class="text-gray-500 text-sm mb-8">Cette action est irréversible. Le créneau tarifaire sera définitivement supprimé.</p>
                    <div class="flex gap-3">
                        <button @click="cancelDelete" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Annuler</button>
                        <button @click="doDelete" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold transition-colors">Supprimer</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
