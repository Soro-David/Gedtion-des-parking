<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: { type: Number, required: true },
    totalPages:  { type: Number, required: true },
    totalItems:  { type: Number, required: true },
    perPage:     { type: Number, default: 10 },
});

const emit = defineEmits(['page-change']);

const from = computed(() => (props.currentPage - 1) * props.perPage + 1);
const to   = computed(() => Math.min(props.currentPage * props.perPage, props.totalItems));

const pages = computed(() => {
    const range = [];
    const delta = 2;
    const left  = props.currentPage - delta;
    const right = props.currentPage + delta;

    for (let i = 1; i <= props.totalPages; i++) {
        if (i === 1 || i === props.totalPages || (i >= left && i <= right)) {
            range.push(i);
        }
    }

    const withDots = [];
    let prev = null;
    for (const p of range) {
        if (prev && p - prev > 1) withDots.push('…');
        withDots.push(p);
        prev = p;
    }
    return withDots;
});
</script>

<template>
    <div v-if="totalPages > 1" class="flex items-center justify-between px-2 py-3 mt-3">
        <!-- Info -->
        <p class="text-xs text-slate-500">
            {{ from }}–{{ to }} sur {{ totalItems }}
        </p>

        <!-- Contrôles -->
        <div class="flex items-center gap-1">
            <!-- Précédent -->
            <button
                @click="emit('page-change', currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                :class="currentPage === 1
                    ? 'text-slate-300 cursor-not-allowed'
                    : 'text-slate-600 hover:bg-slate-100'"
            >
                ‹
            </button>

            <!-- Pages -->
            <template v-for="p in pages" :key="p">
                <span v-if="p === '…'" class="px-1 text-slate-400 text-xs">…</span>
                <button
                    v-else
                    @click="emit('page-change', p)"
                    class="w-7 h-7 rounded-lg text-xs font-bold transition-colors"
                    :class="p === currentPage
                        ? 'bg-[#4A1725] text-white shadow-sm'
                        : 'text-slate-600 hover:bg-slate-100'"
                >
                    {{ p }}
                </button>
            </template>

            <!-- Suivant -->
            <button
                @click="emit('page-change', currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                :class="currentPage === totalPages
                    ? 'text-slate-300 cursor-not-allowed'
                    : 'text-slate-600 hover:bg-slate-100'"
            >
                ›
            </button>
        </div>
    </div>
</template>
