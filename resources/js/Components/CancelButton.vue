<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Annuler',
    },
    color: {
        type: String,
        default: '#787878',
    },
});
</script>

<template>
    <Link
        :href="href"
         class="inline-flex items-center gap-2 px-8 py-3
            bg-[#787878] text-white 
            hover:bg-white hover:text-[#787878] 
            border border-[#787878] 
            text-sm font-semibold rounded-xl 
            shadow-md shadow-[#4A1725]/20 
            transition-all duration-200 
            hover:-translate-y-0.5 active:scale-95 
            whitespace-nowrap"
    >
        {{ label }}
    </Link>
</template>