<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MultiSelectDropdown from '@/Components/MultiSelectDropdown.vue';
import UserFormFields from '@/Components/Layout/UserFormFields.vue';

const props = defineProps({
    users:    Array,
    parkings: { type: Array, default: () => [] },
});

// ── Filtre par rôle ────────────────────────────────────────────────────
const activeRole = ref('all');

const roles = [
    { key: 'all',        label: 'Tous',         color: 'bg-gray-100 text-gray-700' },
    { key: 'supervisor', label: 'Superviseurs',  color: 'bg-purple-100 text-purple-700' },
    { key: 'attendant',  label: 'Agents',        color: 'bg-blue-100 text-blue-700' },
    { key: 'caissier',   label: 'Caissiers',     color: 'bg-green-100 text-green-700' },
];

const roleBadge = {
    supervisor: { label: 'Superviseur', class: 'bg-purple-100 text-purple-700' },
    attendant:  { label: 'Agent',       class: 'bg-blue-100 text-blue-700' },
    caissier:   { label: 'Caissier',    class: 'bg-green-100 text-green-700' },
};

const roleAvatar = {
    supervisor: 'bg-purple-50 text-purple-700 border-purple-100',
    attendant:  'bg-blue-50 text-blue-700 border-blue-100',
    caissier:   'bg-green-50 text-green-700 border-green-100',
};

// ── Pagination ─────────────────────────────────────────────────────────
const PER_PAGE   = 10;
const currentPage = ref(1);

const filtered = computed(() => {
    currentPage.value = 1;
    if (activeRole.value === 'all') return props.users ?? [];
    return (props.users ?? []).filter(u => u.role === activeRole.value);
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));

const paged = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filtered.value.slice(start, start + PER_PAGE);
});

// ── Actions ────────────────────────────────────────────────────────────
const routeForRole = {
    supervisor: { destroy: 'admin.superviseur.destroy', resend: 'admin.superviseur.resend-invitation', edit: 'admin.superviseur.edit', show: 'admin.superviseur.show' },
    attendant:  { destroy: 'admin.agent.destroy',       resend: 'admin.agent.resend-invitation',       edit: 'admin.agent.edit',       show: 'admin.agent.show' },
    caissier:   { destroy: 'admin.caissier.destroy',    resend: 'admin.caissier.resend-invitation',    edit: 'admin.caissier.edit',    show: 'admin.caissier.show' },
};

const confirmDelete = (user) => {
    if (confirm(`Voulez-vous vraiment supprimer ${user.name} ?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};

const resendInvitation = (user) => {
    if (confirm(`Renvoyer l'email d'invitation à ${user.name} ?`)) {
        router.post(route(routeForRole[user.role].resend, user.id));
    }
};

// Compteur par rôle
const countFor = (key) => key === 'all'
    ? (props.users?.length ?? 0)
    : (props.users ?? []).filter(u => u.role === key).length;

// ── Modal Ajouter un Utilisateur ───────────────────────────────────────
const showModal = ref(false);

const form = useForm({
    role:        '',
    name:        '',
    first_name:  '',
    email:       '',
    phone:       '',
    address:     '',
    parking_ids: [],
});

const roleOptions = [
    { value: 'supervisor', label: 'Superviseur',     description: 'Gère les agents et caissiers d\'un parking.' },
    { value: 'attendant',  label: 'Agent de parking', description: 'Enregistre les entrées/sorties des véhicules.' },
    { value: 'caissier',   label: 'Caissier(ère)',    description: 'Gère les paiements et la caisse.' },
];

const needsParking = computed(() =>
    form.role === 'attendant' || form.role === 'caissier' || form.role === 'supervisor'
);

const parkingOptions = computed(() =>
    props.parkings.map(p => ({
        id:    p.id,
        label: `${p.name} (${p.reference})`,
    }))
);

const openModal = () => {
    form.clearErrors();
    form.reset();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    form.post(route('admin.users.store'), {
        preserveState: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <AuthenticatedLayout>
        <template #header>Utilisateurs</template>

        <!-- En-tête -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Gestion des Utilisateurs</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ users.length }} utilisateur{{ users.length > 1 ? 's' : '' }} enregistré{{ users.length > 1 ? 's' : '' }}
                </p>
            </div>

            <PrimaryButton
                @click="openModal"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#143f85] text-white text-sm font-semibold rounded-2xl hover:bg-[#0e2f63] transition-all duration-200 shadow-sm hover:shadow-md"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                Ajouter un Utilisateur
            </PrimaryButton>
        </div>

        <!-- Filtres par rôle -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button
                v-for="r in roles"
                :key="r.key"
                @click="activeRole = r.key"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border transition-all duration-200',
                    activeRole === r.key
                        ? 'border-[#143f85] bg-[#143f85] text-white shadow-sm'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300',
                ]"
            >
                {{ r.label }}
                <span :class="['text-xs px-1.5 py-0.5 rounded-full font-bold', activeRole === r.key ? 'bg-white/20 text-white' : r.color]">
                    {{ countFor(r.key) }}
                </span>
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Rôle</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in paged" :key="user.id" class="hover:bg-gray-50/50 transition-colors group">
                            <!-- Nom -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="['w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg border', roleAvatar[user.role]]"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 group-hover:text-[#143f85] transition-colors">
                                            {{ user.name }} {{ user.first_name }}
                                        </div>
                                        <!-- <div class="text-xs text-gray-400">ID: #{{ user.id.toString().padStart(4, '0') }}</div> -->
                                    </div>
                                </div>
                            </td>
                            <!-- Rôle badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold', roleBadge[user.role]?.class]">
                                    {{ roleBadge[user.role]?.label }}
                                </span>
                            </td>
                            <!-- Email -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ user.email }}</td>
                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(user.created_at).toLocaleDateString('fr-FR') }}
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Voir -->
                                    <Link
                                        :href="route(routeForRole[user.role].show, user.id)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200"
                                        title="Voir le détail"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <!-- Modifier -->
                                    <Link
                                        :href="route(routeForRole[user.role].edit, user.id)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-all duration-200"
                                        title="Modifier"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <!-- Renvoyer invitation -->
                                    <button
                                        v-if="!user.has_password"
                                        @click="resendInvitation(user)"
                                        class="p-2 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-all duration-200"
                                        title="Renvoyer l'invitation"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <!-- Supprimer -->
                                    <button
                                        @click="confirmDelete(user)"
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

                        <tr v-if="paged.length === 0">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="text-lg font-medium">Aucun utilisateur trouvé.</p>
                                    <p class="text-sm mt-1">Cliquez sur « Ajouter un Utilisateur » pour commencer.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :current-page="currentPage"
                :total-pages="totalPages"
                :total-items="filtered.length"
                class="px-4"
                @page-change="currentPage = $event"
            />
        </div>
    </AuthenticatedLayout>

    <!-- ── Modal: Ajouter un Utilisateur ──────────────────────────────── -->
    <Modal :show="showModal" max-width="2xl" @close="closeModal">
        <div class="bg-white rounded-xl overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#143f85] flex items-center justify-center">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Ajouter un Utilisateur</h3>
                        <p class="text-xs text-gray-500">Un email d'invitation sera envoyé automatiquement.</p>
                    </div>
                </div>
                <button
                    @click="closeModal"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                    type="button"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body (scrollable) -->
            <form @submit.prevent="submit" class="px-6 py-5 space-y-5 max-h-[75vh] overflow-y-auto">

                <!-- ── Rôle ─────────────────────────────────────────── -->
                <div>
                    <InputLabel for="role" value="Rôle de l'utilisateur" class="mb-1.5" />
                    <div class="relative">
                        <select
                            id="role"
                            v-model="form.role"
                            :class="[
                                'w-full appearance-none rounded-xl border px-4 py-2.5 pr-10 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#143f85]/20 focus:border-[#143f85] transition-colors',
                                form.errors.role ? 'border-red-400' : 'border-gray-300',
                            ]"
                        >
                            <option value="" disabled>— Sélectionner un rôle —</option>
                            <option
                                v-for="r in roleOptions"
                                :key="r.value"
                                :value="r.value"
                            >
                                {{ r.label }} — {{ r.description }}
                            </option>
                        </select>
                        <!-- Chevron -->
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    <InputError :message="form.errors.role" class="mt-1" />
                </div>
                <!-- ── Champs utilisateur ──────────────────────────── -->
                <UserFormFields :form="form" />
                <!-- ── Identité ──────────────────────────────────────── -->
                <!-- <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="m_name" value="Nom" class="mb-1" />
                        <TextInput
                            id="m_name"
                            v-model="form.name"
                            type="text"
                            class="w-full"
                            placeholder="Ex: Koné"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="m_first_name" value="Prénoms" class="mb-1" />
                        <TextInput
                            id="m_first_name"
                            v-model="form.first_name"
                            type="text"
                            class="w-full"
                            placeholder="Ex: Amadou"
                            required
                        />
                        <InputError :message="form.errors.first_name" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="m_email" value="Email" class="mb-1" />
                        <TextInput
                            id="m_email"
                            v-model="form.email"
                            type="email"
                            class="w-full"
                            placeholder="email@exemple.com"
                            required
                        />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="m_phone" value="Téléphone (optionnel)" class="mb-1" />
                        <TextInput
                            id="m_phone"
                            v-model="form.phone"
                            type="text"
                            class="w-full"
                            placeholder="+225 07 00 00 00 00"
                        />
                        <InputError :message="form.errors.phone" class="mt-1" />
                    </div>
                </div>

                <div>
                    <InputLabel for="m_address" value="Adresse (optionnel)" class="mb-1" />
                    <TextInput
                        id="m_address"
                        v-model="form.address"
                        type="text"
                        class="w-full"
                        placeholder="Ex: Abidjan, Cocody"
                    />
                    <InputError :message="form.errors.address" class="mt-1" />
                </div> -->

                <!-- ── Parkings affectés (Agent & Caissier seulement) ── -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="needsParking">
                        <InputLabel value="Parkings affectés" class="mb-1.5" />
                        <MultiSelectDropdown
                            :options="parkingOptions"
                            v-model="form.parking_ids"
                            placeholder="Sélectionner un ou plusieurs parkings…"
                            :error="form.errors.parking_ids"
                        />
                        <p class="mt-1.5 text-xs text-gray-400 flex items-center gap-1">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Vous pouvez affecter plusieurs parkings à cet agent.
                        </p>
                    </div>
                </Transition>

            </form>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button
                    type="button"
                    @click="closeModal"
                    class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                >
                    Annuler
                </button>
                <PrimaryButton
                    @click="submit"
                    :disabled="form.processing || !form.role"
                    class="px-6 py-2 rounded-xl text-sm"
                >
                    <svg v-if="form.processing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </PrimaryButton>
            </div>

        </div>
    </Modal>

</template>
