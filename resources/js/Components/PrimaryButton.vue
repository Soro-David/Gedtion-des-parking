<script setup>
defineProps({
    as: {
        type: String,
        default: 'button',
    },
    href: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <component
        :is="as"
        :href="href"
        :type="as === 'button' ? type : undefined"
        class="inline-flex items-center justify-center gap-2 px-6 py-3
               bg-[#143f85] text-white
               border border-[#143f85]
               rounded-xl  font-bold text-sm tracking-wide
               focus:outline-none focus:ring-4 focus:ring-[#143f85]/20
               transition-all duration-300
               hover:bg-white hover:text-[#143f85]
               hover:-translate-y-0.5 hover:shadow-lg"
    >
        <slot />
    </component>
</template>