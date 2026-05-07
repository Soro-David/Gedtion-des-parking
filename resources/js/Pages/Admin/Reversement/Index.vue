<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { useAutoRefresh } from '@/Composables/useAutoRefresh.js';

useAutoRefresh();

const props = defineProps({
    reversements:   { type: Array,  default: () => [] },
    totalPending:   { type: Number, default: 0 },
    totalConfirmed: { type: Number, default: 0 },
});

const search = ref('');

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.reversements;
    return props.reversements.filter(r =>
        r.agent_name.toLowerCase().includes(q) ||
        r.agent_role.toLowerCase().includes(q) ||
        (r.note ?? '').toLowerCase().includes(q)
    );
});

const PER_PAGE = 10;
const currentPage = ref(1);
const totalPages  = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));
const paged = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filtered.value.slice(start, start + PER_PAGE);
});
watch(search, () => { currentPage.value = 1; });

const formatAmount = (n) =>
    new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

function confirm(id) {
    router.post(route('admin.reversements.confirm', id));
}
</script>

<template>
    <Head title="Reversements reçus" />
    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-black text-slate-900">Reversements reçus</h1>
                <p class="text-sm text-slate-500 mt-1">Consultez et confirmez les reversements des agents et caissiers.</p>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-semibold">
                {{ $page.props.flash.success }}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">En attente de confirmation</p>
                        <p class="text-2xl font-black text-amber-600">{{ formatAmount(totalPending) }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total confirmé</p>
                        <p class="text-2xl font-black text-emerald-600">{{ formatAmount(totalConfirmed) }}</p>
                    </div>
                </div>
            </div>

            <!-- Recherche -->
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher par agent, rôle, note…"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30"
                />
            </div>

            <!-- Tableau -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div v-if="filtered.length === 0" class="px-6 py-12 text-center">
                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-slate-500 text-sm">Aucun reversement trouvé.</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Agent / Caissier</th>
                            <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Montant</th>
                            <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Sessions</th>
                            <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Note</th>
                            <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="text-left px-5 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in paged" :key="r.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-black text-slate-800">{{ r.agent_name }}</p>
                                <p class="text-xs text-slate-400 capitalize">{{ r.agent_role === 'attendant' ? 'Agent' : 'Caissier' }}</p>
                            </td>
                            <td class="px-5 py-4 font-black text-slate-900">{{ formatAmount(r.amount) }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ r.sessions_count }}</td>
                            <td class="px-5 py-4 text-slate-500 max-w-[180px] truncate">{{ r.note || '—' }}</td>
                            <td class="px-5 py-4 text-slate-500 text-xs">{{ formatDate(r.created_at) }}</td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wide"
                                    :class="r.status === 'confirmed'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="r.status === 'confirmed' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                    {{ r.status === 'confirmed' ? 'Confirmé' : 'En attente' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    v-if="r.status === 'pending'"
                                    @click="confirm(r.id)"
                                    class="px-4 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition-colors"
                                >
                                    Confirmer
                                </button>
                                <span v-else class="text-xs text-slate-400">{{ formatDate(r.confirmed_at) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <Pagination
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :total-items="filtered.length"
                    class="px-4"
                    @page-change="currentPage = $event"
                />
            </div>

        </div>
    </AuthenticatedLayout>
</template>
