<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';
import CancelButton from '@/Components/CancelButton.vue';

useAutoRefresh();

const props = defineProps({
    items:      { type: Array, default: () => [] },
    versements: { type: Array, default: () => [] },
});

// ── Sélection item ───────────────────────────────────────────────────────
const selected = ref(null);
const activeTab = ref('items'); // 'items' | 'history'
const search = ref('');

const filteredItems = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.items;
    return props.items.filter(item =>
        item.parking_name.toLowerCase().includes(q) ||
        item.responsible_name.toLowerCase().includes(q) ||
        (item.responsible_email ?? '').toLowerCase().includes(q)
    );
});

// ── Pagination items ────────────────────────────────────────────────
const PER_PAGE = 10;
const pageItems = ref(1);
const totalPagesItems = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / PER_PAGE)));
const pagedItems = computed(() => {
    const start = (pageItems.value - 1) * PER_PAGE;
    return filteredItems.value.slice(start, start + PER_PAGE);
});
watch(search, () => { pageItems.value = 1; });

// ── Pagination versements ──────────────────────────────────────────
const pageVersements = ref(1);
const totalPagesVersements = computed(() => Math.max(1, Math.ceil(props.versements.length / PER_PAGE)));
const pagedVersements = computed(() => {
    const start = (pageVersements.value - 1) * PER_PAGE;
    return props.versements.slice(start, start + PER_PAGE);
});

// ── Formulaire ───────────────────────────────────────────────────────────
const form = useForm({
    parking_id:  null,
    caissier_id: null,
    agent_id:    null,
    paid_amount: 0,
    note:        '',
});

function selectItem(item) {
    selected.value = item;
    form.parking_id  = item.parking_id;
    form.caissier_id = item.caissier_id;
    form.agent_id    = item.agent_id;
    form.paid_amount = item.total_due;
    form.note = '';
    form.clearErrors();
}

function submit() {
    if (!selected.value) return;
    form.post(route('admin.versements.store'), {
        onSuccess: () => {
            selected.value = null;
            form.reset();
        },
    });
}

// ── Formatage ────────────────────────────────────────────────────────────
const fmt = (n) => new Intl.NumberFormat('fr-FR').format(Math.round(n ?? 0)) + ' FCFA';
const fmtDate = (dt) => dt
    ? new Date(dt).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    : '—';

const roleLabel = (r) => r === 'attendant' ? 'Agent' : 'Caissier';
</script>

<template>
    <Head title="Versements & Caisses (Admin)" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-black text-slate-900">Versements des Parkings</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Gestion globale des versements par caissier actif du parking (ou par agent s'il n'y a pas de caissier).
                </p>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-semibold">
                {{ $page.props.flash.success }}
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b border-slate-200">
                <button
                    @click="activeTab = 'items'"
                    class="px-4 py-2 text-sm font-bold border-b-2 transition-colors"
                    :class="activeTab === 'items' ? 'border-[#4A1725] text-[#4A1725]' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Parkings & Caissiers à verser
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-black bg-[#4A1725]/10 text-[#4A1725]">{{ items.length }}</span>
                </button>
                <button
                    @click="activeTab = 'history'"
                    class="px-4 py-2 text-sm font-bold border-b-2 transition-colors"
                    :class="activeTab === 'history' ? 'border-[#4A1725] text-[#4A1725]' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Historique
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">{{ versements.length }}</span>
                </button>
            </div>

            <!-- ── TAB : Items (Parkings & Caissiers) ────────────────────── -->
            <div v-show="activeTab === 'items'" class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- Liste items -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Recherche -->
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" type="text" placeholder="Rechercher parking, caissier, agent…"
                            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"/>
                    </div>

                    <!-- Empty state -->
                    <div v-if="filteredItems.length === 0" class="text-center py-10 bg-slate-50 rounded-2xl border border-slate-100">
                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm text-slate-400 font-semibold">Aucun encaissement en attente.</p>
                    </div>

                    <!-- Cards items -->
                    <button
                        v-for="item in pagedItems"
                        :key="item.id"
                        @click="selectItem(item)"
                        class="w-full text-left rounded-2xl border p-4 transition-all duration-200"
                        :class="selected?.id === item.id
                            ? 'border-[#4A1725] bg-[#4A1725]/5 shadow-md ring-1 ring-[#4A1725]/20'
                            : 'border-slate-100 bg-white hover:border-[#4A1725]/30 hover:shadow-sm'"
                    >
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <p class="font-black text-slate-900 text-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#4A1725] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    {{ item.parking_name }}
                                </p>
                                <p class="text-xs text-slate-600 font-semibold mt-1">
                                    {{ item.responsible_role === 'caissier' ? 'Caissier responsable :' : 'Agent responsable :' }}
                                    <span class="text-slate-800 font-black">{{ item.responsible_name }}</span>
                                </p>
                            </div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide shrink-0"
                                :class="item.responsible_role === 'caissier' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                                {{ roleLabel(item.responsible_role) }}
                            </span>
                        </div>

                        <!-- Montants -->
                        <div class="space-y-1.5 text-xs bg-slate-50 rounded-xl p-2.5 mt-2">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Collecté (sorties payées)</span>
                                <span class="font-bold text-slate-700">{{ fmt(item.collected_amount) }}</span>
                            </div>
                            <div v-if="item.previous_debt > 0" class="flex justify-between">
                                <span class="text-rose-500 font-semibold">Dette reportée</span>
                                <span class="font-bold text-rose-600">{{ fmt(item.previous_debt) }}</span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-slate-200">
                                <span class="font-black text-slate-800">Montant total à verser</span>
                                <span class="font-black text-[#4A1725] text-sm">{{ fmt(item.total_due) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-2 pt-2 text-[11px] text-slate-400 border-t border-slate-50">
                            <span>Transactions : <strong class="text-slate-600 font-bold">{{ item.sessions_count }}</strong></span>
                            <span>Dernière activité : <strong class="text-slate-600 font-bold">{{ item.last_activity }}</strong></span>
                        </div>
                    </button>
                    <Pagination
                        :current-page="pageItems"
                        :total-pages="totalPagesItems"
                        :total-items="filteredItems.length"
                        @page-change="pageItems = $event"
                    />
                </div>

                <!-- Formulaire versement -->
                <div class="lg:col-span-3">
                    <!-- Placeholder si aucun item sélectionné -->
                    <div v-if="!selected" class="h-full min-h-[320px] flex flex-col items-center justify-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 text-center px-8">
                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        <p class="text-slate-600 font-bold text-sm">Sélectionnez un parking / caissier</p>
                        <p class="text-slate-400 text-xs mt-1">pour afficher les détails des encaissements et enregistrer le versement.</p>
                    </div>

                    <!-- Formulaire -->
                    <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <!-- Header sélectionné -->
                        <div class="bg-[#4A1725]/5 border-b border-[#4A1725]/10 px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="font-black text-slate-900 text-base flex items-center gap-2">
                                    <span>{{ selected.parking_name }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                                        :class="selected.responsible_role === 'caissier' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                                        {{ roleLabel(selected.responsible_role) }}
                                    </span>
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ selected.responsible_role === 'caissier' ? 'Caissier actif' : 'Agent' }} :
                                    <strong class="text-slate-700">{{ selected.responsible_name }}</strong> ({{ selected.responsible_email }})
                                </p>
                            </div>
                            <button @click="selected = null" class="text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Recap financier -->
                        <div class="px-6 py-5 grid grid-cols-3 gap-4 border-b border-slate-100">
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Encaissé</p>
                                <p class="text-xl font-black text-slate-800">{{ fmt(selected.collected_amount) }}</p>
                                <p class="text-[10px] text-slate-400">{{ selected.sessions_count }} transaction(s)</p>
                            </div>
                            <div class="text-center border-x border-slate-100">
                                <p class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mb-1">Dette Reportée</p>
                                <p class="text-xl font-black" :class="selected.previous_debt > 0 ? 'text-rose-600' : 'text-slate-300'">
                                    {{ fmt(selected.previous_debt) }}
                                </p>
                                <p class="text-[10px] text-slate-400">solde antérieur</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-[#4A1725] uppercase tracking-wider mb-1">Montant Total Dû</p>
                                <p class="text-xl font-black text-[#4A1725]">{{ fmt(selected.total_due) }}</p>
                                <p class="text-[10px] text-slate-400">à verser</p>
                            </div>
                        </div>

                        <!-- Formulaire saisie -->
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <p v-if="form.errors.paid_amount" class="text-sm text-red-600 font-medium">
                                {{ form.errors.paid_amount }}
                            </p>

                            <!-- Montant versé -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                    Montant versé (FCFA) *
                                </label>
                                <input
                                    v-model.number="form.paid_amount"
                                    type="number"
                                    min="0"
                                    :max="selected.total_due"
                                    step="1"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-4 py-3 text-lg font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                                    :placeholder="`Max : ${Math.round(selected.total_due).toLocaleString('fr-FR')} FCFA`"
                                />
                                <!-- Aperçu dette restante -->
                                <div v-if="form.paid_amount >= 0 && form.paid_amount < selected.total_due"
                                    class="mt-2 flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-amber-700">
                                        Dette restante : {{ fmt(Math.max(0, selected.total_due - (form.paid_amount || 0))) }}
                                    </span>
                                </div>
                                <div v-else-if="form.paid_amount >= selected.total_due && selected.total_due > 0"
                                    class="mt-2 flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-emerald-700">Solde soldé intégralement</span>
                                </div>
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Note (optionnel)</label>
                                <textarea
                                    v-model="form.note"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30 resize-none"
                                    placeholder="Remarques, contexte du versement…"
                                ></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-1">
                                 <CancelButton
                                    type="button"
                                    @click="selected = null"
                                >
                                    Annuler
                                </CancelButton>
                                <PrimaryButton :disabled="form.processing">
                                    Enregistrer le versement
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── TAB : Historique ──────────────────────────────────────── -->
            <div v-show="activeTab === 'history'">
                <div v-if="versements.length === 0" class="text-center py-16 bg-slate-50 rounded-2xl border border-slate-100">
                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-slate-400 text-sm font-semibold">Aucun versement enregistré.</p>
                </div>

                <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Parking</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Responsable</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Encaissé</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Dette</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Total dû</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Versé</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Reste</th>
                                <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="v in pagedVersements" :key="v.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4 font-bold text-slate-900">
                                    {{ v.parking_name }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-black text-slate-800">{{ v.responsible_name }}</p>
                                    <p class="text-xs text-slate-400">{{ roleLabel(v.responsible_role) }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ fmt(v.collected_amount) }}</td>
                                <td class="px-5 py-4">
                                    <span :class="v.previous_debt > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                        {{ fmt(v.previous_debt) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-black text-slate-800">{{ fmt(v.total_due) }}</td>
                                <td class="px-5 py-4 font-black text-emerald-700">{{ fmt(v.paid_amount) }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black"
                                        :class="v.remaining_debt > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                                    >
                                        {{ v.remaining_debt > 0 ? fmt(v.remaining_debt) : 'Soldé' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-400 text-xs">{{ fmtDate(v.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination
                        :current-page="pageVersements"
                        :total-pages="totalPagesVersements"
                        :total-items="versements.length"
                        class="px-4"
                        @page-change="pageVersements = $event"
                    />
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
