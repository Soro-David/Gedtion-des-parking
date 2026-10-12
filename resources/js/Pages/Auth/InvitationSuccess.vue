<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    role: {
        type: String,
        default: '',
    },
    deepLinkUrl: {
        type: String,
        default: 'parkingapp://login',
    },
    apkUrl: {
        type: String,
        default: '',
    },
});

const isMobile = ref(false);
const autoRedirectAttempted = ref(false);
const countdown = ref(3);

const roleLabel = {
    attendant: 'Agent de parking',
    caissier: 'Caissier',
    supervisor: 'Superviseur',
    admin: 'Administrateur',
}[props.role] || props.role;

const openMobileApp = () => {
    window.location.href = props.deepLinkUrl;
};

onMounted(() => {
    // Détecter si l'appareil est un mobile
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    if (/android|iphone|ipad|ipod/i.test(userAgent)) {
        isMobile.value = true;
        
        // Décompte puis ouverture automatique du deep link
        const timer = setInterval(() => {
            if (countdown.value > 1) {
                countdown.value--;
            } else {
                clearInterval(timer);
                autoRedirectAttempted.value = true;
                openMobileApp();
            }
        }, 1000);
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Compte activé avec succès" />

        <div class="text-center">
            <!-- Animated Success Icon -->
            <div class="relative w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                <div class="absolute inset-0 bg-green-100 rounded-full animate-ping opacity-30"></div>
                <div class="relative w-20 h-20 bg-gradient-to-tr from-[#487119] to-emerald-500 rounded-full flex items-center justify-center shadow-lg shadow-green-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <!-- Header Info -->
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Félicitations {{ name }} !</h2>
            <div v-if="role" class="mt-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    Compte {{ roleLabel }} activé
                </span>
            </div>
            <p class="text-sm text-gray-600 mt-3 leading-relaxed">
                Votre mot de passe a été configuré avec succès. Vous pouvez maintenant utiliser l'application mobile pour débuter vos opérations.
            </p>

            <!-- Auto-redirect notification on Mobile -->
            <div v-if="isMobile && !autoRedirectAttempted" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-2xl flex items-center justify-center gap-2 text-xs text-green-900 font-semibold animate-pulse">
                <svg class="animate-spin h-4 w-4 text-green-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Lancement de l'application dans <strong>{{ countdown }}s</strong>...</span>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 space-y-3">
                <!-- Bouton Ouvrir Application Mobile -->
                <button
                    @click="openMobileApp"
                    type="button"
                    class="w-full flex items-center justify-center gap-3 py-3.5 px-5 bg-gradient-to-r from-[#487119] to-[#3d5e15] hover:from-[#3d5e15] hover:to-[#2e4710] text-white font-bold rounded-2xl shadow-lg shadow-green-900/20 hover:shadow-xl hover:scale-[1.01] active:scale-[0.99] transition-all duration-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Ouvrir l'application Mobile</span>
                </button>

                <!-- Bouton Télécharger l'application -->
                <a
                    v-if="apkUrl"
                    :href="apkUrl"
                    class="w-full flex items-center justify-center gap-2 py-3 px-5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-2xl transition-all duration-200 text-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Télécharger l'APK mobile</span>
                </a>

                <!-- Lien Connexion Web -->
                <div class="pt-2">
                    <Link
                        :href="route('login')"
                        class="text-xs text-gray-500 hover:text-gray-900 font-medium inline-flex items-center gap-1.5 transition-colors duration-200"
                    >
                        <span>Se connecter sur le navigateur Web</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
