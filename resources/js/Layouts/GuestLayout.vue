<script setup>
import Footer from '@/Components/Layout/Footer.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-50 relative overflow-hidden">
        <!-- Background Decorations -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#4A1725]/10 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute top-1/2 -right-24 w-80 h-80 bg-purple-100 rounded-full blur-3xl opacity-50"></div>
        </div>

        <div class="z-10 w-full sm:max-w-md flex-grow flex flex-col justify-center pt-6 sm:pt-0">
            <div class="flex justify-center mb-10 animate-bounce-slow">
                <Link href="/">
                    <img
                        src="/Logo_Plateau.svg"
                        alt="Plateau Smart City"
                        class="h-20 w-auto object-contain drop-shadow-lg hover:opacity-90 transition-opacity duration-300"
                    />
                </Link>
            </div>

            <div class="w-full overflow-hidden bg-white/80 backdrop-blur-xl px-10 py-12 shadow-2xl shadow-indigo-100/50 sm:rounded-[2.5rem] border border-white relative">
                <slot />
            </div>
        </div>

        <!-- <div class="w-full z-10">
            <Footer />
        </div> -->
    </div>
</template>

<style>
.animate-bounce-slow {
    animation: bounce 3s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>
