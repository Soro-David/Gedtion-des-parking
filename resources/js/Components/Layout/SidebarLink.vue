<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() =>
    props.active
        ? 'flex items-center px-4 py-3 text-white bg-[#143f85] rounded-lg group transition-all duration-200 overflow-hidden'
        : 'flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-lg group transition-all duration-200 overflow-hidden'
);
</script>

<template>
    <Link :href="href" :class="classes">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <slot name="icon" />
            </div>
            <span 
                v-show="!isCollapsed"
                class="ms-3 font-medium text-sm whitespace-nowrap transition-opacity duration-300"
            >
                <slot />
            </span>
        </div>
    </Link>
</template>
