<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const values = [
    {
        icon: '⚡',
        title: 'Temps réel',
        description: 'Toutes les informations de disponibilité sont mises à jour instantanément pour vous offrir une vision précise.',
    },
    {
        icon: '🔒',
        title: 'Sécurisé',
        description: 'Notre système garantit la confidentialité des données et la sécurité de chaque transaction.',
    },
    {
        icon: '📍',
        title: 'Géolocalisé',
        description: 'Localisez chaque zone de parking et obtenez un itinéraire personnalisé en un seul clic.',
    },
    {
        icon: '📊',
        title: 'Analytique',
        description: 'Tableaux de bord complets pour suivre les revenus, l\'occupation et l\'activité de vos parkings.',
    },
    {
        icon: '👥',
        title: 'Multi-rôles',
        description: 'Superadmin, Admin, Superviseur, Caissier, Agent : chaque acteur dispose de son espace dédié.',
    },
    {
        icon: '📷',
        title: 'OCR Plaques',
        description: 'Reconnaissance automatique des plaques d\'immatriculation pour un enregistrement rapide et sans erreur.',
    },
];

const team = [
    { name: 'KKS Technologies', role: 'Développeur & Éditeur logiciel', initial: 'K' },
];
</script>

<template>
    <Head title="À propos – Plateau Parck" />
    <PublicLayout>

        <!-- ── Hero ────────────────────────────────────────────────── -->
        <section class="relative bg-gradient-to-br from-slate-900 to-[#143f85] text-white overflow-hidden">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDM0djZoNnYtNmgtNnptMCAwdi02aC02djZoNnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-40"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 backdrop-blur rounded-full text-xs font-extrabold uppercase tracking-widest mb-8">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    Qui sommes-nous ?
                </div>
                <h1 class="text-5xl lg:text-7xl font-black tracking-tight leading-tight mb-6">
                    À propos de <span class="text-amber-300">Gespark</span>
                </h1>
                <p class="text-xl text-white/70 max-w-2xl mx-auto leading-relaxed">
                    Une solution moderne pensée pour simplifier la gestion des parkings et améliorer l'expérience des usagers.
                </p>
            </div>
        </section>

        <!-- ── Mission ─────────────────────────────────────────────── -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-[#143f85] mb-4">Notre mission</p>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight mb-6">
                        Rendre le stationnement<br>
                        <span class="text-[#143f85]">intelligent et accessible.</span>
                    </h2>
                    <p class="text-slate-600 leading-relaxed mb-6">
                        Gespark est une plateforme de gestion de parkings développée pour répondre aux enjeux contemporains de mobilité urbaine. Notre objectif est de connecter les conducteurs aux places disponibles en temps réel tout en donnant aux opérateurs les outils nécessaires pour gérer efficacement leurs infrastructures.
                    </p>
                    <p class="text-slate-600 leading-relaxed mb-8">
                        Grâce à des technologies modernes — reconnaissance de plaques, géolocalisation, tableau de bord analytique — Gespark transforme la gestion de parking en une expérience fluide, transparente et rentable.
                    </p>
                    <Link :href="route('public.contact')" class="inline-block px-8 py-3.5 rounded-xl bg-[#143f85] text-white font-black text-sm hover:bg-[#0e2f6e] transition-colors duration-200 shadow-lg shadow-[#143f85]/20">
                        Nous contacter
                    </Link>
                </div>

                <!-- Stats visuelles -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-[#143f85] text-white rounded-2xl p-8 flex flex-col justify-center">
                        <p class="text-5xl font-black mb-2">100%</p>
                        <p class="text-white/70 text-sm font-semibold">Données en temps réel</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-2xl p-8 flex flex-col justify-center">
                        <p class="text-5xl font-black text-amber-700 mb-2">6</p>
                        <p class="text-amber-600 text-sm font-semibold">Niveaux d'accès</p>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-8 flex flex-col justify-center">
                        <p class="text-5xl font-black text-emerald-700 mb-2">24/7</p>
                        <p class="text-emerald-600 text-sm font-semibold">Suivi de l'activité</p>
                    </div>
                    <div class="bg-slate-900 text-white rounded-2xl p-8 flex flex-col justify-center">
                        <p class="text-5xl font-black mb-2">OCR</p>
                        <p class="text-white/70 text-sm font-semibold">Reconnaissance auto des plaques</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Fonctionnalités ──────────────────────────────────────── -->
        <section class="bg-slate-50 border-t border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="text-center mb-14">
                    <p class="text-xs font-black uppercase tracking-widest text-[#143f85] mb-3">Ce que nous offrons</p>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight">Fonctionnalités clés</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="item in values"
                        :key="item.title"
                        class="bg-white rounded-2xl p-7 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
                    >
                        <div class="w-12 h-12 rounded-xl bg-[#143f85]/10 flex items-center justify-center text-2xl mb-5">
                            {{ item.icon }}
                        </div>
                        <h3 class="text-lg font-black text-slate-900 mb-2">{{ item.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Qui développe Gespark ────────────────────────────────── -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <p class="text-xs font-black uppercase tracking-widest text-[#143f85] mb-4">L'équipe derrière le projet</p>
            <h2 class="text-4xl font-black text-slate-900 tracking-tight mb-12">Développé avec passion</h2>
            <div class="flex justify-center">
                <div
                    v-for="member in team"
                    :key="member.name"
                    class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 max-w-xs text-center hover:shadow-lg transition-shadow duration-300"
                >
                    <div class="w-20 h-20 rounded-2xl bg-[#143f85] text-white flex items-center justify-center text-3xl font-black mx-auto mb-5 shadow-lg shadow-[#143f85]/20">
                        {{ member.initial }}
                    </div>
                    <h3 class="font-black text-slate-900 text-lg mb-1">{{ member.name }}</h3>
                    <p class="text-sm text-slate-500">{{ member.role }}</p>
                </div>
            </div>
        </section>

        <!-- ── CTA ─────────────────────────────────────────────────── -->
        <section class="bg-[#143f85]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                <h2 class="text-4xl font-black text-white mb-4">Prêt à rejoindre Gespark ?</h2>
                <p class="text-white/70 mb-8 max-w-xl mx-auto">Contactez-nous pour en savoir plus sur nos solutions ou pour déployer Gespark dans votre infrastructure.</p>
                <div class="flex justify-center gap-4 flex-wrap">
                    <Link :href="route('public.contact')" class="px-8 py-4 rounded-2xl bg-white text-[#143f85] font-black text-sm hover:bg-blue-50 transition-colors duration-200 shadow-xl">
                        Nous contacter
                    </Link>
                    <Link :href="route('login')" class="px-8 py-4 rounded-2xl border border-white/30 text-white font-black text-sm hover:bg-white/10 transition-colors duration-200">
                        Se connecter
                    </Link>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>
