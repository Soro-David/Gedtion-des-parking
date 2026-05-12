<script setup>
/**
 * ParkingSelector — select list of parkings with checkbox/radio UI.
 * Props:
 *   parkings:    Array<{ id, name, reference, available_spots, capacity }>
 *   modelValue:  Array<Number>  (selected parking IDs)
 *   singleSelect: Boolean — if true, only one parking can be selected at a time
 *   error:       String
 */
const props = defineProps({
    parkings:     { type: Array,   default: () => [] },
    modelValue:   { type: Array,   default: () => [] },
    singleSelect: { type: Boolean, default: false },
    error:        { type: String,  default: '' },
});

const emit = defineEmits(['update:modelValue']);

const toggle = (id) => {
    if (props.singleSelect) {
        // En mode single : si déjà sélectionné on désélectionne, sinon on remplace
        emit('update:modelValue', props.modelValue.includes(id) ? [] : [id]);
    } else {
        const current = [...props.modelValue];
        const idx = current.indexOf(id);
        if (idx === -1) {
            current.push(id);
        } else {
            current.splice(idx, 1);
        }
        emit('update:modelValue', current);
    }
};

const isSelected = (id) => props.modelValue.includes(id);
</script>

<template>
    <div>
        <p v-if="parkings.length === 0" class="text-sm text-gray-400 italic">Aucun parking actif disponible.</p>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button
                v-for="p in parkings"
                :key="p.id"
                type="button"
                @click="toggle(p.id)"
                :class="[
                    'flex items-start gap-3 rounded-2xl border-2 p-4 text-left transition-all duration-150 hover:shadow-sm',
                    isSelected(p.id)
                        ? 'border-[#487119] bg-green-50'
                        : 'border-gray-200 bg-white hover:border-gray-300',
                ]"
            >
                <!-- Radio / Checkbox indicator -->
                <div
                    :class="[
                        'mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center border-2 transition-colors',
                        singleSelect ? 'rounded-full' : 'rounded-md',
                        isSelected(p.id) ? 'border-[#487119] bg-[#487119]' : 'border-gray-300 bg-white',
                    ]"
                >
                    <svg v-if="isSelected(p.id)" class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <!-- Info -->
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-gray-800">{{ p.name }}</p>
                    <p class="text-xs text-gray-400">Réf: {{ p.reference }}</p>
                    <p class="mt-1 text-xs font-medium" :class="p.available_spots > 0 ? 'text-green-600' : 'text-red-500'">
                        {{ p.available_spots }} / {{ p.capacity }} places libres
                    </p>
                </div>
            </button>
        </div>

        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
