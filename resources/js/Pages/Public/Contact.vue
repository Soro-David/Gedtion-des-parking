<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const page = usePage();

const form = useForm({
    name:    '',
    email:   '',
    subject: '',
    message: '',
});

function submit() {
    form.post(route('public.contact.send'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const contactInfos = [
    {
        icon: '📍',
        title: 'Adresse',
        lines: ['Abidjan, Côte d\'Ivoire'],
    },
    {
        icon: '🕐',
        title: 'Horaires d\'assistance',
        lines: ['Lun – Ven : 08h00 – 18h00', 'Sam : 09h00 – 13h00'],
    },
    {
        icon: '✉️',
        title: 'Email',
        lines: ['contact@kks-technologies.ci'],
    },
];
</script>

<template>
    <Head title="Contact – Plateau Parck" />
    <PublicLayout>

        <!-- ── Hero ────────────────────────────────────────────────── -->
        <section class="relative bg-gradient-to-br from-[#143f85] via-[#1a52b0] to-slate-900 text-white overflow-hidden">
            <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 backdrop-blur rounded-full text-xs font-extrabold uppercase tracking-widest mb-8">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    On est là pour vous aider
                </div>
                <h1 class="text-5xl lg:text-6xl font-black tracking-tight leading-tight mb-4">
                    Contactez-<span class="text-amber-300">nous</span>
                </h1>
                <p class="text-xl text-white/70 max-w-xl mx-auto">
                    Une question, un partenariat, ou une demande d'assistance ? Écrivez-nous, nous répondons rapidement.
                </p>
            </div>
        </section>

        <!-- ── Contenu principal ────────────────────────────────────── -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                <!-- Infos de contact -->
                <div class="space-y-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-[#143f85] mb-2">Informations</p>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Parlons-nous</h2>
                    </div>

                    <div
                        v-for="info in contactInfos"
                        :key="info.title"
                        class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm"
                    >
                        <div class="flex items-start gap-4">
                            <span class="text-2xl">{{ info.icon }}</span>
                            <div>
                                <h3 class="font-black text-slate-900 mb-1">{{ info.title }}</h3>
                                <p v-for="line in info.lines" :key="line" class="text-sm text-slate-500">{{ line }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Liens vers les zones -->
                    <div class="bg-[#143f85] text-white rounded-2xl p-6">
                        <h3 class="font-black text-lg mb-2">Voir les zones de parking</h3>
                        <p class="text-white/70 text-sm mb-4">Consultez les disponibilités en temps réel et obtenez un itinéraire.</p>
                        <Link :href="route('public.accueil') + '#zones'" class="inline-block px-6 py-2.5 rounded-xl bg-white text-[#143f85] font-black text-sm hover:bg-blue-50 transition-colors duration-200">
                            Voir les zones
                        </Link>
                    </div>
                </div>

                <!-- Formulaire de contact -->
                <div class="lg:col-span-2">
                    <!-- Message de succès -->
                    <div
                        v-if="$page.props.flash?.success"
                        class="mb-6 flex items-start gap-3 bg-emerald-50 border border-emerald-200 rounded-2xl p-5"
                    >
                        <span class="text-emerald-500 text-xl">✅</span>
                        <div>
                            <p class="font-black text-emerald-800">Message envoyé !</p>
                            <p class="text-emerald-700 text-sm mt-0.5">{{ $page.props.flash.success }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 space-y-6">
                        <h2 class="text-2xl font-black text-slate-900 mb-2">Envoyer un message</h2>
                        <p class="text-slate-500 text-sm mb-6">Tous les champs sont obligatoires.</p>

                        <!-- Nom + Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="name" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">
                                    Votre nom
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Jean Dupont"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-sm font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#143f85]/20 focus:border-[#143f85] transition-all duration-200"
                                    :class="{ 'border-red-400 bg-red-50': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500 font-semibold">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">
                                    Adresse e-mail
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="jean@exemple.com"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-sm font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#143f85]/20 focus:border-[#143f85] transition-all duration-200"
                                    :class="{ 'border-red-400 bg-red-50': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500 font-semibold">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <!-- Sujet -->
                        <div>
                            <label for="subject" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">
                                Sujet
                            </label>
                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                placeholder="Ex: Demande de renseignement"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-sm font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#143f85]/20 focus:border-[#143f85] transition-all duration-200"
                                :class="{ 'border-red-400 bg-red-50': form.errors.subject }"
                            />
                            <p v-if="form.errors.subject" class="mt-1.5 text-xs text-red-500 font-semibold">{{ form.errors.subject }}</p>
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">
                                Message
                            </label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="6"
                                placeholder="Décrivez votre demande en détail..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-sm font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#143f85]/20 focus:border-[#143f85] transition-all duration-200 resize-none"
                                :class="{ 'border-red-400 bg-red-50': form.errors.message }"
                            ></textarea>
                            <p v-if="form.errors.message" class="mt-1.5 text-xs text-red-500 font-semibold">{{ form.errors.message }}</p>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-4 rounded-xl bg-[#143f85] text-white font-black text-sm hover:bg-[#0e2f6e] disabled:opacity-60 disabled:cursor-not-allowed transition-all duration-200 shadow-lg shadow-[#143f85]/20 flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ form.processing ? 'Envoi en cours…' : 'Envoyer le message' }}
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- ── FAQ rapide ────────────────────────────────────────────── -->
        <section class="bg-slate-50 border-t border-slate-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="text-center mb-12">
                    <p class="text-xs font-black uppercase tracking-widest text-[#143f85] mb-3">Foire aux questions</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">Questions fréquentes</h2>
                </div>
                <div class="space-y-4">
                    <details
                        v-for="faq in [
                            { q: 'Comment consulter la disponibilité des parkings ?', a: 'Rendez-vous sur la page d\'accueil et consultez la section « Zones de stationnement ». Les informations sont actualisées en temps réel.' },
                            { q: 'Comment obtenir un itinéraire pour un parking ?', a: 'Sur la carte de chaque zone, cliquez sur « Google Maps » ou « OpenStreetMap » pour obtenir un itinéraire depuis votre position actuelle.' },
                            { q: 'Que signifie « zone saturée » ?', a: 'Une zone est considérée saturée lorsque plus de 90% de sa capacité est occupée. Il est conseillé de vous orienter vers une zone alternative.' },
                            { q: 'Je suis un opérateur, comment accéder à la gestion ?', a: 'Connectez-vous via le bouton « Connexion » en haut de page avec vos identifiants fournis par l\'administrateur.' },
                        ]"
                        :key="faq.q"
                        class="bg-white rounded-2xl border border-slate-100 shadow-sm px-6 py-4 group"
                    >
                        <summary class="flex items-center justify-between cursor-pointer font-black text-slate-900 text-sm select-none py-1">
                            {{ faq.q }}
                            <span class="ml-4 text-[#143f85] text-lg font-black transition-transform duration-200 group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-sm text-slate-500 leading-relaxed pb-1">{{ faq.a }}</p>
                    </details>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>
