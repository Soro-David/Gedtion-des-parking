<script setup>
/**
 * MultiSelectDropdown — Select2-like searchable multi-select for Vue 3 + Tailwind.
 * Props:
 *   options      : Array<{ id: number|string, label: string }>
 *   modelValue   : Array<number|string>  — selected IDs
 *   placeholder  : String
 *   error        : String
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    options:     { type: Array,  default: () => [] },
    modelValue:  { type: Array,  default: () => [] },
    placeholder: { type: String, default: 'Sélectionner…' },
    error:       { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const isOpen    = ref(false);
const search    = ref('');
const container = ref(null);

const filtered = computed(() =>
    props.options.filter(o =>
        o.label.toLowerCase().includes(search.value.toLowerCase())
    )
);

const isSelected = (id) => props.modelValue.includes(id);

const toggle = (id) => {
    const current = [...props.modelValue];
    const idx = current.indexOf(id);
    if (idx === -1) current.push(id);
    else current.splice(idx, 1);
    emit('update:modelValue', current);
};

const remove = (id, e) => {
    e.stopPropagation();
    emit('update:modelValue', props.modelValue.filter(v => v !== id));
};

const selectedItems = computed(() =>
    props.options.filter(o => props.modelValue.includes(o.id))
);

const handleClickOutside = (e) => {
    if (container.value && !container.value.contains(e.target)) {
        isOpen.value = false;
        search.value  = '';
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div ref="container" class="relative">
        <!-- Trigger box -->
        <div
            @click="isOpen = !isOpen"
            :class="[
                'min-h-[42px] w-full flex flex-wrap items-center gap-1.5 rounded-xl border px-3 py-2 cursor-pointer transition-colors bg-white',
                isOpen  ? 'border-[#143f85] ring-2 ring-[#143f85]/20' : 'border-gray-300 hover:border-gray-400',
                error   ? 'border-red-400 ring-1 ring-red-400/30' : '',
            ]"
        >
            <!-- Selected tags -->
            <template v-if="selectedItems.length > 0">
                <span
                    v-for="item in selectedItems"
                    :key="item.id"
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#143f85]/10 text-[#143f85] text-xs font-semibold rounded-lg"
                >
                    {{ item.label }}
                    <button
                        type="button"
                        @click="remove(item.id, $event)"
                        class="hover:text-red-500 transition-colors leading-none"
                        tabindex="-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </template>
            <span v-else class="text-sm text-gray-400 select-none">{{ placeholder }}</span>

            <!-- Chevron -->
            <div class="ml-auto pl-1 flex-shrink-0">
                <svg
                    :class="['w-4 h-4 text-gray-400 transition-transform duration-150', isOpen ? 'rotate-180' : '']"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Dropdown panel -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-y-95 -translate-y-1"
            enter-to-class="opacity-100 scale-y-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-y-100 translate-y-0"
            leave-to-class="opacity-0 scale-y-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-1 w-full bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden origin-top"
            >
                <!-- Search input -->
                <div class="p-2 border-b border-gray-100">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-2 h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher un parking…"
                            class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#143f85] focus:ring-1 focus:ring-[#143f85]/20"
                            @click.stop
                            autofocus
                        />
                    </div>
                </div>

                <!-- Options list -->
                <div class="max-h-52 overflow-y-auto">
                    <p v-if="filtered.length === 0" class="px-4 py-4 text-sm text-gray-400 text-center italic">
                        Aucun résultat
                    </p>

                    <button
                        v-for="opt in filtered"
                        :key="opt.id"
                        type="button"
                        @click="toggle(opt.id)"
                        :class="[
                            'w-full flex items-center gap-3 px-3 py-2.5 text-sm text-left transition-colors',
                            isSelected(opt.id)
                                ? 'bg-[#143f85]/5 text-[#143f85] font-medium'
                                : 'text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <!-- Checkbox visual -->
                        <div
                            :class="[
                                'flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-colors',
                                isSelected(opt.id) ? 'border-[#143f85] bg-[#143f85]' : 'border-gray-300 bg-white',
                            ]"
                        >
                            <svg v-if="isSelected(opt.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        {{ opt.label }}
                    </button>
                </div>

                <!-- Footer: nb selected -->
                <div v-if="modelValue.length > 0" class="px-3 py-2 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ modelValue.length }} sélectionné{{ modelValue.length > 1 ? 's' : '' }}</span>
                    <button
                        type="button"
                        @click.stop="emit('update:modelValue', []); search = ''"
                        class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors"
                    >
                        Tout effacer
                    </button>
                </div>
            </div>
        </Transition>

        <p v-if="error" class="mt-1.5 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
