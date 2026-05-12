<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2  px-6 py-3
               bg-[#787878] text-white
               border border-[#787878]
               rounded-xl text-xs font-semibold uppercase tracking-widest
               shadow-sm
               transition-all duration-200
               hover:bg-white hover:text-[#787878]
               hover:-translate-y-0.5 hover:shadow-md
               focus:outline-none focus:ring-2 focus:ring-[#787878]/20 focus:ring-offset-2
               disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <slot />
    </button>
</template>