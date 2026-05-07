<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: false,
        default: null,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center gap-2 px-5 py-2.5 
            bg-[#143f85] text-white 
            hover:bg-white hover:text-[#143f85] 
            border border-[#143f85] 
            text-sm font-semibold rounded-xl 
            shadow-md shadow-[#143f85]/20 
            transition-all duration-200 
            hover:-translate-y-0.5 active:scale-95 
            whitespace-nowrap"
    >
        <template v-if="icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path :d="icon" />
            </svg>
        </template>
        {{ label }}
    </Link>
</template>