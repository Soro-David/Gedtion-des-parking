<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const mobileMenuOpen = ref(false);
const scrolled = ref(false);

const navLinks = [
    { label: 'Accueil',   href: route('public.accueil'),          routeName: 'public.accueil'  },
    { label: 'Nos zones', href: route('public.accueil') + '#zones', routeName: null              },
    { label: 'À propos',  href: route('public.apropos'),           routeName: 'public.apropos'  },
    { label: 'Contact',   href: route('public.contact'),           routeName: 'public.contact'  },
];

function isActive(link) {
    if (!link.routeName) return false;
    if (link.routeName === 'public.accueil') return page.url === '/';
    if (link.routeName === 'public.apropos') return page.url.startsWith('/a-propos');
    if (link.routeName === 'public.contact') return page.url.startsWith('/contact');
    return false;
}

function handleScroll() {
    scrolled.value = window.scrollY > 10;
}

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col">

        <!-- ── Navigation ──────────────────────────────────────────── -->
        <header
            class="sticky top-0 z-50 transition-all duration-300"
            :class="scrolled
                ? 'bg-[#FDF6EE] shadow-md shadow-black/8'
                : 'bg-[#FDF6EE]'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">

                    <!-- Logo Plateau -->
                    <Link :href="route('public.accueil')" class="flex-shrink-0 hover:opacity-90 transition-opacity duration-200">
                        <img
                            src="/Logo_Plateau.svg"
                            alt="Plateau Park"
                            class="h-9 w-auto"
                        />
                    </Link>

                    <!-- Desktop nav links (centre) -->
                    <nav class="hidden lg:flex items-center gap-1">
                        <a
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            class="relative px-4 py-2 text-sm font-semibold transition-all duration-200 rounded-lg"
                            :class="isActive(link)
                                ? 'text-[#143f85]'
                                : 'text-slate-700 hover:text-[#143f85] hover:bg-[#143f85]/5'"
                        >
                            {{ link.label }}
                            <!-- underline active -->
                            <span
                                v-if="isActive(link)"
                                class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-4 h-0.5 rounded-full bg-[#143f85]"
                            ></span>
                        </a>
                    </nav>

                    <!-- Boutons CTA (desktop) + burger (mobile) -->
                    <div class="flex items-center gap-3">

                        <!-- Desktop CTA -->
                        <div class="hidden md:flex items-center gap-2">
                            <!-- Parcourir / Dashboard -->
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('dashboard')"
                                class="px-5 py-2.5 rounded-full bg-[#2AAB66] text-white text-sm font-bold hover:bg-[#22925a] transition-colors duration-200 shadow-sm"
                            >
                                Tableau de bord
                            </Link>

                            <!-- Connexion -->
                            <Link
                                :href="route('login')"
                                class="px-5 py-2.5 rounded-xl bg-[#cc1f1f] text-white text-sm font-bold hover:bg-[#a71b1b] transition-colors duration-200 shadow-sm"
                            >
                                Connexion
                            </Link>
                        </div>

                        <!-- Mobile burger -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl text-slate-700 hover:bg-slate-200/60 transition-all duration-200"
                            aria-label="Menu"
                        >
                            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="mobileMenuOpen" class="lg:hidden border-t border-slate-200/60 bg-[#FDF6EE] shadow-lg">
                    <div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
                        <a
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            @click="mobileMenuOpen = false"
                            class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200"
                            :class="isActive(link)
                                ? 'bg-[#143f85]/10 text-[#143f85]'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-[#143f85]'"
                        >
                            {{ link.label }}
                        </a>

                        <div class="pt-3 border-t border-slate-200/60 mt-2 flex flex-col gap-2">
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('dashboard')"
                                class="flex items-center justify-center w-full px-4 py-3 rounded-full bg-[#2AAB66] text-white text-sm font-bold hover:bg-[#22925a] transition-colors duration-200"
                            >
                                Tableau de bord
                            </Link>
                            <!-- <a
                                v-else
                                href="#zones"
                                @click="mobileMenuOpen = false"
                                class="flex items-center justify-center w-full px-4 py-3 rounded-full bg-[#2AAB66] text-white text-sm font-bold hover:bg-[#22925a] transition-colors duration-200"
                            >
                                Parcourir
                            </a> -->
                            <Link
                                :href="route('login')"
                                class="flex items-center justify-center w-full px-4 py-3 rounded-full bg-[#cc1f1f] text-white text-sm font-bold hover:bg-[#a71b1b] transition-colors duration-200"
                            >
                                Connexion
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </header>

        <!-- ── Main content ────────────────────────────────────────── -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- ── Footer ──────────────────────────────────────────────── -->
        <footer class="bg-[#0a1f47] text-white">
            <!-- Top accent -->
            <div class="h-0.5 bg-gradient-to-r from-blue-400 via-sky-300 to-blue-500 w-full"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

                    <!-- Brand -->
                    <div>
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 bg-white/10 border border-white/20 rounded-xl flex items-center justify-center">
                                <ApplicationLogo class="w-6 h-6 fill-current text-white" />
                            </div>
                            <div>
                                <span class="text-lg font-black text-white block">Gespark<span class="text-sky-300">.</span></span>
                                <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Gestion de Parking</span>
                            </div>
                        </div>
                        <p class="text-sm text-white/50 leading-relaxed">
                            Solution intelligente de gestion de parking.<br>
                            Temps réel, simple, efficace.
                        </p>
                    </div>

                    <!-- Navigation -->
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-widest text-sky-300 mb-5">Navigation</h4>
                        <ul class="space-y-3">
                            <li v-for="link in navLinks" :key="link.label">
                                <a :href="link.href" class="flex items-center gap-2 text-sm text-white/60 hover:text-sky-300 transition-colors duration-200 font-medium group">
                                    <span class="w-1 h-1 rounded-full bg-sky-400/50 group-hover:bg-sky-400 transition-colors"></span>
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact info -->
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-widest text-sky-300 mb-5">Contact</h4>
                        <ul class="space-y-3 text-sm">
                            <li class="flex items-start gap-3 text-white/60">
                                <span class="text-sky-300 mt-0.5">📍</span>
                                <span>Abidjan, Côte d'Ivoire</span>
                            </li>
                            <li class="flex items-start gap-3 text-white/60">
                                <span class="text-sky-300 mt-0.5">✉</span>
                                <Link :href="route('public.contact')" class="hover:text-sky-300 transition-colors">Nous contacter</Link>
                            </li>
                        </ul>

                        <div class="mt-6">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-[#143f85] text-xs font-black hover:bg-blue-50 transition-colors duration-200"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Accès opérateurs
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/10 mt-12 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3">
                    <p class="text-xs text-white/30">&copy; {{ new Date().getFullYear() }} Gespark. Tous droits réservés.</p>
                    <p class="text-xs font-bold text-white/30 uppercase tracking-widest">Développé par KKS-TECHNOLOGIES</p>
                </div>
            </div>
        </footer>
    </div>
</template>
