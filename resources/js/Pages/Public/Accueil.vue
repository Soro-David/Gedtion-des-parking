<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    parkings: { type: Array, default: () => [] },
});

// ── Hero Slideshow (static images) ─────────────────────────────────────
const heroSlides = [
    { src: '/slides/parking1.jpg', alt: 'Parking moderne géré par Gespark' },
    { src: '/slides/parking2.jpg', alt: 'Gestion intelligente des places' },
    { src: '/slides/parking3.jpg', alt: 'Contrôle en temps réel de votre parking' },
];
const heroCurrentSlide = ref(0);
let heroTimer = null;

function heroNext() {
    heroCurrentSlide.value = (heroCurrentSlide.value + 1) % heroSlides.length;
}

// ── Slider (parking images from DB) ─────────────────────────────────────
const currentSlide = ref(0);
let slideInterval = null;

const slideImages = computed(() =>
    props.parkings.filter(p => p.image)
);

function nextSlide() {
    if (slideImages.value.length > 0)
        currentSlide.value = (currentSlide.value + 1) % slideImages.value.length;
}

function prevSlide() {
    if (slideImages.value.length > 0)
        currentSlide.value = (currentSlide.value - 1 + slideImages.value.length) % slideImages.value.length;
}

function goToSlide(i) {
    currentSlide.value = i;
    // Reset timer on manual navigation
    if (slideInterval) {
        clearInterval(slideInterval);
        if (slideImages.value.length > 1)
            slideInterval = setInterval(nextSlide, 5000);
    }
}

onMounted(() => {
    heroTimer = setInterval(heroNext, 5000);
    if (slideImages.value.length > 1)
        slideInterval = setInterval(nextSlide, 5000);
});

onUnmounted(() => {
    clearInterval(heroTimer);
    if (slideInterval) clearInterval(slideInterval);
});

// Filtre actif
const filter = ref('tous');

const filtered = computed(() => {
    if (filter.value === 'tous') return props.parkings;
    return props.parkings.filter(p => p.saturation === filter.value);
});

const stats = computed(() => ({
    total:      props.parkings.length,
    disponible: props.parkings.filter(p => p.saturation === 'disponible').length,
    modere:     props.parkings.filter(p => p.saturation === 'modéré').length,
    sature:     props.parkings.filter(p => p.saturation === 'saturé').length,
    totalPlaces:    props.parkings.reduce((s, p) => s + p.capacity, 0),
    placesLibres:   props.parkings.reduce((s, p) => s + p.available, 0),
}));

function saturationConfig(sat) {
    const map = {
        disponible: { label: 'Disponible', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', bar: 'bg-emerald-500', dot: 'bg-emerald-500' },
        modéré:     { label: 'Modéré',     bg: 'bg-amber-50',   text: 'text-amber-700',   border: 'border-amber-200',   bar: 'bg-amber-500',   dot: 'bg-amber-500'   },
        saturé:     { label: 'Saturé',     bg: 'bg-red-50',     text: 'text-red-700',     border: 'border-red-200',     bar: 'bg-red-500',     dot: 'bg-red-500'     },
    };
    return map[sat] ?? map['disponible'];
}

function googleMapsUrl(parking) {
    if (parking.latitude && parking.longitude) {
        return `https://www.google.com/maps/dir/?api=1&destination=${parking.latitude},${parking.longitude}`;
    }
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(parking.name + ' ' + parking.address)}`;
}

function openStreetMapUrl(parking) {
    if (parking.latitude && parking.longitude) {
        return `https://www.openstreetmap.org/directions?to=${parking.latitude}%2C${parking.longitude}`;
    }
    return `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(parking.address)}&format=html`;
}
</script>

<template>
    <Head title="Accueil – Plateau Parck" />
    <PublicLayout>

        <!-- ── Hero ────────────────────────────────────────────────── -->
        <section class="relative text-white overflow-hidden">
            <!-- Hero Slideshow background -->
            <div class="absolute inset-0 z-0">
                <transition-group name="hero-fade" tag="div" class="absolute inset-0">
                    <div
                        v-for="(slide, i) in heroSlides"
                        :key="i"
                        v-show="i === heroCurrentSlide"
                        class="absolute inset-0 bg-cover bg-center"
                        :style="{ backgroundImage: `url(${slide.src})` }"
                        :aria-label="slide.alt"
                    ></div>
                </transition-group>
                <!-- Dark overlay -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/65 via-black/45 to-black/70"></div>
            </div>

            <!-- Slide indicators -->
            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-10 flex items-center gap-2.5">
                <button
                    v-for="(slide, i) in heroSlides"
                    :key="i"
                    @click="heroCurrentSlide = i"
                    class="transition-all duration-300 rounded-full focus:outline-none"
                    :class="i === heroCurrentSlide
                        ? 'w-8 h-2.5 bg-white'
                        : 'w-2.5 h-2.5 bg-white/40 hover:bg-white/70'"
                    :aria-label="`Slide ${i + 1}`"
                ></button>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-36 flex flex-col items-center text-center">
                <div class="max-w-3xl">
                    <h1 class="text-5xl lg:text-7xl font-black tracking-tight leading-tight mb-6">
                        Trouvez votre<br>
                        <span class="text-amber-300">place de parking</span><br>
                        en un instant.
                    </h1>

                    <p class="text-xl text-white/70 leading-relaxed mb-10 max-w-xl mx-auto">
                        Consultez la disponibilité des parkings en temps réel, identifiez les zones saturées et obtenez un itinéraire pour y accéder facilement.
                    </p>

                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="#zones" class="px-8 py-4 rounded-2xl bg-white text-[#143f85] font-black text-sm hover:bg-blue-50 transition-colors duration-200 shadow-xl">
                            Voir les zones
                        </a>
                        <Link :href="route('public.contact')" class="px-8 py-4 rounded-2xl border border-white/30 text-white font-black text-sm hover:bg-white/10 transition-colors duration-200">
                            Nous contacter
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Slider parkings ─────────────────────────────────────── -->
        <section v-if="slideImages.length > 0" class="relative w-full bg-slate-900 overflow-hidden" style="height: 420px;">
            <!-- Slides -->
            <div class="relative w-full h-full">
                <transition-group name="fade-slide" tag="div" class="w-full h-full">
                    <div
                        v-for="(parking, i) in slideImages"
                        :key="parking.id"
                        v-show="currentSlide === i"
                        class="absolute inset-0 w-full h-full"
                    >
                        <img
                            :src="'/storage/' + parking.image"
                            :alt="parking.name"
                            class="w-full h-full object-cover"
                        />
                        <!-- Gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>

                        <!-- Caption -->
                        <div class="absolute bottom-12 left-0 right-0 px-8 text-white">
                            <div class="max-w-3xl mx-auto">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black border backdrop-blur-sm mb-3"
                                    :class="[saturationConfig(parking.saturation).bg, saturationConfig(parking.saturation).text, saturationConfig(parking.saturation).border]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="saturationConfig(parking.saturation).dot"></span>
                                    {{ saturationConfig(parking.saturation).label }}
                                </span>
                                <h3 class="text-3xl font-black drop-shadow-lg">{{ parking.name }}</h3>
                                <p v-if="parking.address" class="text-white/70 text-sm mt-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ parking.address }}
                                </p>
                            </div>
                        </div>
                    </div>
                </transition-group>
            </div>

            <!-- Prev / Next buttons -->
            <button
                v-if="slideImages.length > 1"
                @click="prevSlide"
                class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 backdrop-blur-sm text-white flex items-center justify-center transition-colors duration-200"
                aria-label="Précédent"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                v-if="slideImages.length > 1"
                @click="nextSlide"
                class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 backdrop-blur-sm text-white flex items-center justify-center transition-colors duration-200"
                aria-label="Suivant"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Dots -->
            <div v-if="slideImages.length > 1" class="absolute bottom-4 left-0 right-0 z-20 flex justify-center gap-2">
                <button
                    v-for="(_, i) in slideImages"
                    :key="i"
                    @click="goToSlide(i)"
                    class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                    :class="currentSlide === i ? 'bg-white scale-125' : 'bg-white/40 hover:bg-white/70'"
                    :aria-label="'Slide ' + (i + 1)"
                ></button>
            </div>

            <!-- Compteur -->
            <div v-if="slideImages.length > 1" class="absolute top-4 right-4 z-20 px-3 py-1 rounded-full bg-black/40 backdrop-blur-sm text-white text-xs font-bold">
                {{ currentSlide + 1 }} / {{ slideImages.length }}
            </div>
        </section>

        <!-- ── Statistiques globales ────────────────────────────────── -->
        <section class="bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-3xl font-black text-slate-900">{{ stats.total }}</p>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Zones de parking</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-black text-slate-900">{{ stats.totalPlaces }}</p>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Places totales</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-black text-emerald-600">{{ stats.placesLibres }}</p>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Places libres</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-black text-red-600">{{ stats.sature }}</p>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Zones saturées</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Zones de stationnement ───────────────────────────────── -->
        <section id="zones" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

            <!-- En-tête + filtres -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-10">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-[#143f85] mb-2">Disponibilité en direct</p>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight">Nos zones de stationnement</h2>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-for="f in ['tous', 'disponible', 'modéré', 'saturé']"
                        :key="f"
                        @click="filter = f"
                        class="px-4 py-2 rounded-full text-xs font-bold capitalize transition-all duration-200"
                        :class="filter === f
                            ? 'bg-[#143f85] text-white shadow-md'
                            : 'bg-white border border-slate-200 text-slate-600 hover:border-[#143f85]/40'"
                    >
                        {{ f === 'tous' ? 'Tous' : f }}
                    </button>
                </div>
            </div>

            <!-- Grille de cards -->
            <div v-if="filtered.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div
                    v-for="parking in filtered"
                    :key="parking.id"
                    class="group bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden"
                >
                    <!-- Image ou placeholder -->
                    <div class="relative h-40 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
                        <img
                            v-if="parking.image"
                            :src="'/storage/' + parking.image"
                            :alt="parking.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>

                        <!-- Badge saturation -->
                        <div class="absolute top-3 right-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black border backdrop-blur-sm"
                                :class="[saturationConfig(parking.saturation).bg, saturationConfig(parking.saturation).text, saturationConfig(parking.saturation).border]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="saturationConfig(parking.saturation).dot"></span>
                                {{ saturationConfig(parking.saturation).label }}
                            </span>
                        </div>
                    </div>

                    <!-- Corps -->
                    <div class="p-5">
                        <h3 class="font-black text-slate-900 text-lg mb-1 truncate">{{ parking.name }}</h3>
                        <p class="text-sm text-slate-500 mb-4 line-clamp-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ parking.address || '—' }}
                        </p>

                        <!-- Jauges places -->
                        <div class="flex gap-3 mb-4">
                            <div class="flex-1 text-center bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                                <p class="text-2xl font-black text-emerald-700">{{ parking.available }}</p>
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Libres</p>
                            </div>
                            <div class="flex-1 text-center bg-rose-50 rounded-xl p-3 border border-rose-100">
                                <p class="text-2xl font-black text-rose-700">{{ parking.occupied }}</p>
                                <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Occupées</p>
                            </div>
                            <div class="flex-1 text-center bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <p class="text-2xl font-black text-slate-700">{{ parking.capacity }}</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total</p>
                            </div>
                        </div>

                        <!-- Barre de saturation -->
                        <div class="mb-5">
                            <div class="flex justify-between text-xs text-slate-500 font-semibold mb-1.5">
                                <span>Taux d'occupation</span>
                                <span>{{ parking.pct }}%</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="saturationConfig(parking.saturation).bar"
                                    :style="{ width: parking.pct + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Itinéraires -->
                        <div class="flex gap-2">
                            <a
                                :href="googleMapsUrl(parking)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-[#143f85] text-white text-xs font-bold hover:bg-[#0e2f6e] transition-colors duration-200"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                                Google Maps
                            </a>
                            <a
                                :href="openStreetMapUrl(parking)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:border-[#143f85]/30 hover:text-[#143f85] transition-colors duration-200"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                OpenStreetMap
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aucun résultat -->
            <div v-else class="text-center py-20">
                <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-black text-slate-700 mb-2">Aucune zone trouvée</h3>
                <p class="text-slate-500 text-sm">Essayez un autre filtre.</p>
            </div>
        </section>

        <!-- ── Légende saturation ───────────────────────────────────── -->
        <section class="bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <h2 class="text-2xl font-black mb-8 text-center">Comment lire les indicateurs de saturation ?</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-4 h-4 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            <span class="font-black text-emerald-400">Disponible</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">Moins de 60% des places occupées. Vous trouverez facilement une place.</p>
                    </div>
                    <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-4 h-4 rounded-full bg-amber-500 flex-shrink-0"></span>
                            <span class="font-black text-amber-400">Modéré</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">Entre 60% et 89% d'occupation. Quelques places restantes, dépêchez-vous.</p>
                    </div>
                    <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-4 h-4 rounded-full bg-red-500 flex-shrink-0"></span>
                            <span class="font-black text-red-400">Saturé</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">Plus de 90% d'occupation. Privilegiez une zone alternative.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA final ────────────────────────────────────────────── -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <h2 class="text-4xl font-black text-slate-900 mb-4">Une question ? Besoin d'aide ?</h2>
            <p class="text-slate-500 mb-8 max-w-xl mx-auto">Notre équipe est disponible pour vous accompagner et répondre à toutes vos questions.</p>
            <Link :href="route('public.contact')" class="inline-block px-10 py-4 rounded-2xl bg-[#143f85] text-white font-black text-sm hover:bg-[#0e2f6e] transition-colors duration-200 shadow-xl shadow-[#143f85]/20">
                Contacter l'équipe
            </Link>
        </section>

    </PublicLayout>
</template>

<style scoped>
/* Hero crossfade transition */
.hero-fade-enter-active,
.hero-fade-leave-active {
    transition: opacity 1s ease;
    position: absolute;
    inset: 0;
}
.hero-fade-enter-from,
.hero-fade-leave-to {
    opacity: 0;
}

/* Slide fade transition */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 0.8s ease, transform 0.8s ease;
    position: absolute;
    inset: 0;
}
.fade-slide-enter-from {
    opacity: 0;
    transform: scale(1.03);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
.fade-slide-enter-to,
.fade-slide-leave-from {
    opacity: 1;
    transform: scale(1);
}
</style>
