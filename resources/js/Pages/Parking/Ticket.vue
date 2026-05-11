<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    session: { type: Object, required: true },
    role:    { type: String, default: 'caissier' },
});

const indexRoute = props.role === 'caissier'
    ? route('caissier.parking-sessions.index')
    : route('attendant.parking-sessions.index');

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

const printTicket = () => window.print();
</script>

<template>
    <Head :title="`Ticket - ${session.license_plate}`" />

    <!-- Boutons d'action (masqués à l'impression) -->
    <div class="no-print min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6 gap-4">
        <div class="flex gap-3 mb-2">
            <Link
                :href="indexRoute"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl text-sm font-semibold transition-all shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                retour à la Liste des stationnements
            </Link>
            <button
                @click="printTicket"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-sm font-bold shadow-md transition-all active:scale-95"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer le ticket
            </button>
            <Link
                :href="newRoute"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-bold shadow-md transition-all active:scale-95"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau stationnement
            </Link>
        </div>

        <!-- Ticket physique -->
        <div class="ticket-wrapper bg-white rounded-2xl shadow-xl overflow-hidden" style="width:340px;">

            <!-- En-tête -->
            <div class="ticket-header bg-orange-600 text-white text-center px-6 py-5">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2"/>
                    </svg>
                    <span class="text-lg font-black tracking-wide uppercase">Ticket de Stationnement</span>
                </div>
                <p class="text-orange-100 text-xs">Conservez ce ticket jusqu'à votre sortie</p>
            </div>

            <!-- Séparateur en dents de scie -->
            <div class="ticket-cut bg-gray-100 relative" style="height:16px;">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100 border-none"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100 border-none"></div>
                <div class="border-t-2 border-dashed border-gray-300 mx-4 mt-2"></div>
            </div>

            <!-- Plaque d'immatriculation centrale -->
            <div class="px-6 py-6 text-center">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Immatriculation</p>
                <div class="inline-block border-4 border-gray-800 rounded-xl px-8 py-3 bg-yellow-50">
                    <span class="text-3xl font-black text-gray-900 tracking-[0.25em] uppercase">
                        {{ session.license_plate }}
                    </span>
                </div>
                <p v-if="session.marque || session.modele" class="mt-3 text-sm text-gray-600 font-medium">
                    {{ [session.marque, session.modele].filter(Boolean).join(' ') }}
                </p>
            </div>

            <!-- Séparateur -->
            <div class="ticket-cut bg-gray-100 relative" style="height:16px;">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="border-t-2 border-dashed border-gray-300 mx-4 mt-2"></div>
            </div>

            <!-- Informations -->
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
                    <span class="text-sm font-bold text-orange-700">{{ formatDate(session.started_at) }}</span>
                </div>
                <div v-if="session.agent_name" class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">Agent</span>
                    <span class="text-sm text-gray-700">{{ session.agent_name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">N° Ticket</span>
                    <span class="text-xs font-mono text-gray-500">#{{ String(session.id).padStart(6, '0') }}</span>
                </div>
            </div>

            <!-- Pied du ticket -->
            <div class="bg-gray-50 border-t border-dashed border-gray-200 px-6 py-4 text-center">
                <p class="text-xs text-gray-400">Merci de présenter ce ticket à la sortie.</p>
                <p class="text-xs text-gray-300 mt-1">Le tarif sera calculé à votre sortie.</p>
            </div>
        </div>
    </div>

    <!-- Version impression uniquement -->
    <div class="print-only" style="display:none;">
        <div style="width:80mm; font-family:monospace; font-size:12px; padding:4mm 6mm; border:1px solid #000;">
            <div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:4mm; margin-bottom:4mm;">
                <div style="font-size:16px; font-weight:bold; letter-spacing:2px;">TICKET DE STATIONNEMENT</div>
                <div style="font-size:10px; color:#555;">Conservez ce ticket jusqu'à votre sortie</div>
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
                <table style="width:100%; font-size:11px;">
                    <tr>
                        <td style="color:#555;">Parking :</td>
                        <td style="text-align:right; font-weight:bold;">{{ session.parking_name || '—' }}</td>
                    </tr>
                    <tr v-if="session.parking_ref">
                        <td style="color:#555;">Réf :</td>
                        <td style="text-align:right;">{{ session.parking_ref }}</td>
                    </tr>
                    <tr>
                        <td style="color:#555;">Entrée :</td>
                        <td style="text-align:right; font-weight:bold;">{{ formatDate(session.started_at) }}</td>
                    </tr>
                    <tr v-if="session.agent_name">
                        <td style="color:#555;">Agent :</td>
                        <td style="text-align:right;">{{ session.agent_name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#555;">N° :</td>
                        <td style="text-align:right; font-family:monospace;">#{{ String(session.id).padStart(6, '0') }}</td>
                    </tr>
                </table>
            </div>

            <div style="border-top:1px dashed #000; margin-top:4mm; padding-top:3mm; text-align:center; font-size:10px; color:#555;">
                Présentez ce ticket à la sortie
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
