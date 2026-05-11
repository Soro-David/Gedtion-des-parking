<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    session: { type: Object, required: true },
    role:    { type: String, default: 'caissier' },
});

const historyRoute = props.role === 'caissier'
    ? route('caissier.history')
    : route('attendant.history');

const newRoute = props.role === 'caissier'
    ? route('caissier.parking-sessions.create')
    : route('attendant.parking-sessions.create');

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
};

const formatDuration = (minutes) => {
    if (!minutes && minutes !== 0) return '—';
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m ? `${h}h ${m}min` : `${h}h`;
};

const printReceipt = () => window.print();
</script>

<template>
    <Head :title="`Reçu - ${session.license_plate}`" />

    <!-- Boutons d'action (masqués à l'impression) -->
    <div class="no-print min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6 gap-4">
        <div class="flex gap-3 mb-2">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl text-sm font-semibold transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </a>
            <Link
                :href="historyRoute"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl text-sm font-semibold transition-all shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Historique
            </Link>
            <button
                @click="printReceipt"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-bold shadow-md transition-all active:scale-95"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer le reçu
            </button>
            <Link
                :href="newRoute"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-sm font-bold shadow-md transition-all active:scale-95"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau stationnement
            </Link>
        </div>

        <!-- Reçu visuel -->
        <div class="ticket-wrapper bg-white rounded-2xl shadow-xl overflow-hidden" style="width:340px;">

            <!-- En-tête -->
            <div class="bg-green-600 text-white text-center px-6 py-5">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-lg font-black tracking-wide uppercase">Reçu de Stationnement</span>
                </div>
                <p class="text-green-100 text-xs">Sortie enregistrée avec succès</p>
            </div>

            <!-- Séparateur -->
            <div class="relative bg-gray-100" style="height:16px;">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="border-t-2 border-dashed border-gray-300 mx-4 mt-2"></div>
            </div>

            <!-- Plaque + véhicule -->
            <div class="px-6 py-5 text-center">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Immatriculation</p>
                <div class="inline-block border-4 border-gray-800 rounded-xl px-8 py-3 bg-yellow-50">
                    <span class="text-3xl font-black text-gray-900 tracking-[0.25em] uppercase">
                        {{ session.license_plate }}
                    </span>
                </div>
                <p v-if="session.marque || session.modele" class="mt-2 text-sm text-gray-600 font-medium">
                    {{ [session.marque, session.modele].filter(Boolean).join(' ') }}
                </p>
            </div>

            <!-- Séparateur -->
            <div class="relative bg-gray-100" style="height:16px;">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="border-t-2 border-dashed border-gray-300 mx-4 mt-2"></div>
            </div>

            <!-- Détails -->
            <div class="px-6 py-5 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Parking</span>
                    <span class="text-sm font-bold text-gray-800">{{ session.parking_name || '—' }}</span>
                </div>
                <div v-if="session.parking_ref" class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Référence</span>
                    <span class="text-sm font-semibold text-gray-700">{{ session.parking_ref }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Entrée</span>
                    <span class="text-sm text-gray-700">{{ formatDate(session.started_at) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Sortie</span>
                    <span class="text-sm text-gray-700">{{ formatDate(session.ended_at) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Durée</span>
                    <span class="text-sm font-bold text-gray-800">{{ formatDuration(session.duration_minutes) }}</span>
                </div>
                <div v-if="session.closer_name" class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Encaissé par</span>
                    <span class="text-sm text-gray-700">{{ session.closer_name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">N° Reçu</span>
                    <span class="text-xs font-mono text-gray-500">#{{ String(session.id).padStart(6, '0') }}</span>
                </div>
            </div>

            <!-- Montant -->
            <div class="mx-4 mb-4 rounded-2xl bg-green-50 border-2 border-green-200 px-5 py-4 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Montant payé</p>
                <p v-if="session.amount !== null && session.amount !== undefined" class="text-4xl font-black text-green-700">
                    {{ Number(session.amount).toLocaleString('fr-FR') }}
                    <span class="text-lg font-semibold">FCFA</span>
                </p>
                <p v-else class="text-sm text-gray-400 italic">Aucun tarif défini</p>
            </div>

            <!-- Pied -->
            <div class="bg-gray-50 border-t border-dashed border-gray-200 px-6 py-4 text-center">
                <p class="text-xs text-gray-400">Merci pour votre stationnement !</p>
            </div>
        </div>
    </div>

    <!-- Version impression uniquement -->
    <div class="print-only" style="display:none;">
        <div style="width:80mm; font-family:monospace; font-size:12px; padding:4mm 6mm; border:1px solid #000;">
            <div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:4mm; margin-bottom:4mm;">
                <div style="font-size:16px; font-weight:bold; letter-spacing:2px;">REÇU DE STATIONNEMENT</div>
                <div style="font-size:10px; color:#555;">Sortie enregistrée avec succès</div>
            </div>

            <div style="text-align:center; margin:4mm 0;">
                <div style="font-size:10px; letter-spacing:2px; color:#555; margin-bottom:2mm;">IMMATRICULATION</div>
                <div style="font-size:22px; font-weight:bold; letter-spacing:4px; border:2px solid #000; display:inline-block; padding:2mm 6mm;">
                    {{ session.license_plate }}
                </div>
                <div v-if="session.marque || session.modele" style="font-size:11px; margin-top:2mm;">
                    {{ [session.marque, session.modele].filter(Boolean).join(' ') }}
                </div>
            </div>

            <div style="border-top:1px dashed #000; padding-top:4mm; margin-top:4mm;">
                <table style="width:100%; font-size:11px; border-collapse:collapse;">
                    <tr>
                        <td style="color:#555; padding:1mm 0;">Parking :</td>
                        <td style="text-align:right; font-weight:bold;">{{ session.parking_name || '—' }}</td>
                    </tr>
                    <tr v-if="session.parking_ref">
                        <td style="color:#555; padding:1mm 0;">Réf :</td>
                        <td style="text-align:right;">{{ session.parking_ref }}</td>
                    </tr>
                    <tr>
                        <td style="color:#555; padding:1mm 0;">Entrée :</td>
                        <td style="text-align:right;">{{ formatDate(session.started_at) }}</td>
                    </tr>
                    <tr>
                        <td style="color:#555; padding:1mm 0;">Sortie :</td>
                        <td style="text-align:right;">{{ formatDate(session.ended_at) }}</td>
                    </tr>
                    <tr>
                        <td style="color:#555; padding:1mm 0;">Durée :</td>
                        <td style="text-align:right; font-weight:bold;">{{ formatDuration(session.duration_minutes) }}</td>
                    </tr>
                    <tr v-if="session.closer_name">
                        <td style="color:#555; padding:1mm 0;">Agent :</td>
                        <td style="text-align:right;">{{ session.closer_name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#555; padding:1mm 0;">N° :</td>
                        <td style="text-align:right; font-family:monospace;">#{{ String(session.id).padStart(6, '0') }}</td>
                    </tr>
                </table>
            </div>

            <div style="border-top:2px solid #000; margin-top:4mm; padding-top:3mm; text-align:center;">
                <div style="font-size:11px; color:#555; margin-bottom:1mm;">MONTANT PAYÉ</div>
                <div v-if="session.amount !== null && session.amount !== undefined" style="font-size:24px; font-weight:bold;">
                    {{ Number(session.amount).toLocaleString('fr-FR') }} FCFA
                </div>
                <div v-else style="font-size:11px; color:#999;">Aucun tarif défini</div>
            </div>

            <div style="border-top:1px dashed #000; margin-top:4mm; padding-top:3mm; text-align:center; font-size:10px; color:#555;">
                Merci pour votre stationnement !
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; margin: 0; }
}
@media screen {
    .print-only { display: none !important; }
}
</style>
