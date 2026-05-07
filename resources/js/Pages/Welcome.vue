<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Footer from '@/Components/Layout/Footer.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const slides = [
    { src: '/slides/parking1.jpg', alt: 'Parking moderne géré par Gespark' },
    { src: '/slides/parking2.jpg', alt: 'Gestion intelligente des places' },
    { src: '/slides/parking3.jpg', alt: 'Contrôle en temps réel de votre parking' },
];

const current = ref(0);
let timer = null;

function next() {
    current.value = (current.value + 1) % slides.length;
}

function goTo(index) {
    current.value = index;
    resetTimer();
}

function resetTimer() {
    clearInterval(timer);
    timer = setInterval(next, 5000);
}

onMounted(() => {
    timer = setInterval(next, 5000);
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>

<template>
    <Head title="Bienvenue chez Gespark" />

    <div class="relative min-h-screen overflow-hidden flex flex-col">

        <!-- ── Slideshow Background ── -->
        <div class="absolute inset-0 z-0">
            <transition-group name="slide-fade" tag="div" class="absolute inset-0">
                <div
                    v-for="(slide, i) in slides"
                    :key="i"
                    v-show="i === current"
                    class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000"
                    :style="{ backgroundImage: `url(${slide.src})` }"
                    :aria-label="slide.alt"
                ></div>
            </transition-group>
            <!-- Dark overlay for readability -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>
        </div>

        <!-- ── Navigation ── -->
        <nav class="relative z-20 px-6 py-7 flex justify-between items-center max-w-7xl mx-auto w-full">
            <div class="flex items-center space-x-3 group">
                <div class="w-12 h-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-500">
                    <ApplicationLogo class="w-8 h-8 fill-current text-white" />
                </div>
                <span class="text-2xl font-black text-white tracking-tighter drop-shadow">
                    Gespark<span class="text-[#e8a0af]">.</span>
                </span>
            </div>

            <div v-if="canLogin" class="flex items-center space-x-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="btn-gradient px-6 py-2.5 rounded-xl font-bold text-sm tracking-wide"
                >
                    Tableau de bord
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-white/80 hover:text-white font-bold text-sm px-4 py-2 transition-colors duration-300"
                    >
                        Connexion
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="btn-gradient px-6 py-2.5 rounded-xl font-bold text-sm tracking-wide"
                    >
                        S'inscrire
                    </Link>
                </template>
            </div>
        </nav>

        <!-- ── Hero Content ── -->
        <main class="relative z-20 flex-1 flex flex-col items-center justify-center px-6 text-center max-w-5xl mx-auto">
            <div class="inline-flex items-center space-x-2 px-4 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full shadow-sm mb-8 animate-fade-in">
                <span class="flex h-2 w-2 rounded-full bg-[#e8a0af]"></span>
                <span class="text-xs font-extrabold text-white uppercase tracking-widest">Nouvelle Solution de Parking</span>
            </div>

            <h1 class="text-6xl md:text-8xl font-black text-white tracking-tightest leading-tight mb-8 drop-shadow-lg animate-fade-in stagger-1">
                La gestion de parking <br/>
                <span class="text-[#e8a0af]">réinventée.</span>
            </h1>

            <p class="text-xl text-white/80 max-w-2xl leading-relaxed mb-12 animate-fade-in stagger-2 drop-shadow">
                Une plateforme intuitive, sécurisée et performante pour gérer vos parkings, vos agents et vos revenus en temps réel.
            </p>

            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-6 animate-fade-in stagger-3">
                <Link
                    :href="route('login')"
                    class="btn-gradient px-10 py-5 rounded-[2rem] font-black text-lg tracking-wide hover:scale-105 transition-transform duration-300 shadow-2xl shadow-[#4A1725]/50"
                >
                    Démarrer maintenant
                </Link>
                <button class="bg-white/10 backdrop-blur-md border border-white/30 px-10 py-5 rounded-[2rem] font-black text-lg tracking-wide text-white hover:bg-white/20 transition-all duration-300 shadow-sm">
                    Découvrir plus
                </button>
            </div>

            <!-- ── Slide Indicators ── -->
            <div class="flex items-center space-x-3 mt-14">
                <button
                    v-for="(slide, i) in slides"
                    :key="i"
                    @click="goTo(i)"
                    class="transition-all duration-300 rounded-full focus:outline-none"
                    :class="i === current
                        ? 'w-8 h-2.5 bg-white'
                        : 'w-2.5 h-2.5 bg-white/40 hover:bg-white/70'"
                    :aria-label="`Aller au slide ${i + 1}`"
                ></button>
            </div>
        </main>

        <!-- ── Footer ── -->
        <div class="relative z-20 w-full">
            <Footer />
        </div>
    </div>
</template>

<style scoped>
.tracking-tightest {
    letter-spacing: -0.05em;
}

/* Slide crossfade transition */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 1s ease;
    position: absolute;
    inset: 0;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
}
</style>
