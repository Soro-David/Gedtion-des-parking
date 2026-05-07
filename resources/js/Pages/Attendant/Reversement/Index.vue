<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    pendingAmount:   { type: Number, default: 0 },
    pendingSessions: { type: Number, default: 0 },
    reversements:    { type: Array,  default: () => [] },
    receivers:       { type: Array,  default: () => [] },
});

const page = usePage();
const showForm = ref(false);

const form = useForm({
    receiver_id: '',
    note: '',
});

function submit() {
    form.post(route('attendant.reversements.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
}

const formatAmount = (n) =>
    new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const pending   = computed(() => props.reversements.filter(r => r.status === 'pending'));
const confirmed = computed(() => props.reversements.filter(r => r.status === 'confirmed'));
</script>

<template>
    <Head title="Mes Reversements" />
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Reversements</h1>
                    <p class="text-sm text-slate-500 mt-1">Gérez vos reversements de fonds vers l'admin ou le superviseur.</p>
                </div>
                <button
                    v-if="pendingAmount > 0"
                    @click="showForm = !showForm"
                    class="px-5 py-2.5 bg-[#4A1725] text-white rounded-xl font-bold text-sm hover:bg-[#3a1020] transition-colors"
                >
                    {{ showForm ? 'Annuler' : 'Effectuer un reversement' }}
                </button>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-semibold">
                {{ $page.props.flash.success }}
            </div>

            <!-- Solde en attente -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Solde à reverser</p>
                        <p class="text-2xl font-black text-amber-600">{{ formatAmount(pendingAmount) }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ pendingSessions }} sortie(s) non reversée(s)</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total reversé</p>
                        <p class="text-2xl font-black text-emerald-600">{{ formatAmount(reversements.filter(r => r.status === 'confirmed').reduce((s, r) => s + r.amount, 0)) }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ confirmed.length }} reversement(s) confirmé(s)</p>
                    </div>
                </div>
            </div>

            <!-- Formulaire de reversement -->
            <div v-if="showForm" class="bg-white rounded-2xl border border-[#4A1725]/20 shadow-sm p-6 space-y-4">
                <h2 class="font-black text-slate-800 text-base">Nouveau reversement — {{ formatAmount(pendingAmount) }}</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Erreur générale -->
                    <p v-if="form.errors.general" class="text-sm text-red-600 font-medium">{{ form.errors.general }}</p>

                    <!-- Récepteur -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Reverser à *</label>
                        <select
                            v-model="form.receiver_id"
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                            required
                        >
                            <option value="" disabled>— Choisir un admin ou superviseur —</option>
                            <option v-for="r in receivers" :key="r.id" :value="r.id">
                                {{ r.name }} ({{ r.role === 'admin' ? 'Admin' : 'Superviseur' }})
                            </option>
                        </select>
                        <p v-if="form.errors.receiver_id" class="text-xs text-red-500 mt-1">{{ form.errors.receiver_id }}</p>
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Note (optionnel)</label>
                        <textarea
                            v-model="form.note"
                            rows="2"
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30 resize-none"
                            placeholder="Remarques, contexte…"
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.receiver_id"
                            class="px-6 py-2.5 bg-[#4A1725] text-white rounded-xl font-bold text-sm hover:bg-[#3a1020] disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Envoi…' : 'Confirmer le reversement' }}
                        </button>
                        <button type="button" @click="showForm = false" class="px-6 py-2.5 border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>

            <!-- Message si rien à reverser -->
            <div v-if="pendingAmount === 0 && !showForm" class="bg-slate-50 rounded-2xl border border-slate-100 px-6 py-8 text-center">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-slate-500 text-sm font-medium">Aucun montant en attente de reversement.</p>
            </div>

            <!-- Historique -->
            <div v-if="reversements.length > 0" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="font-black text-slate-800 text-base">Historique des reversements</h2>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-for="r in reversements" :key="r.id" class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-black text-slate-900 text-sm">{{ formatAmount(r.amount) }}</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide"
                                    :class="r.status === 'confirmed'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ r.status === 'confirmed' ? 'Confirmé' : 'En attente' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">Vers : <span class="font-semibold text-slate-700">{{ r.receiver }}</span></p>
                            <p v-if="r.note" class="text-xs text-slate-400 mt-0.5 truncate">{{ r.note }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-slate-400">{{ formatDate(r.created_at) }}</p>
                            <p v-if="r.confirmed_at" class="text-xs text-emerald-600 font-semibold">Confirmé le {{ formatDate(r.confirmed_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
