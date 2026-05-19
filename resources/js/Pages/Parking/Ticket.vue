<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import QRCode from 'qrcode';

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

// ─── QR code avec logo "P" centré ──────────────────────────────
const qrDataUrl      = ref('');
const qrDataUrlPrint = ref('');

const generateQRWithLogo = async (text, size) => {
    const canvas = document.createElement('canvas');
    canvas.width  = size;
    canvas.height = size;

    // errorCorrectionLevel H obligatoire pour supporter le logo superposé
    await QRCode.toCanvas(canvas, text, {
        width: size,
        margin: 1,
        errorCorrectionLevel: 'H',
        color: { dark: '#111827', light: '#ffffff' },
    });

    const ctx = canvas.getContext('2d');
    const cx  = size / 2;
    const cy  = size / 2;
    const r   = Math.round(size * 0.13);

    // Halo blanc autour du logo pour isoler les modules QR
    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    ctx.arc(cx, cy, r + 5, 0, Math.PI * 2);
    ctx.fill();

    // Cercle bleu
    ctx.fillStyle = '#143f85';
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fill();

    // Lettre "P" blanche centrée
    ctx.fillStyle    = '#ffffff';
    ctx.font         = `900 ${Math.round(r * 1.45)}px Arial, sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('P', cx, cy + 1);

    return canvas.toDataURL('image/png');
};

onMounted(async () => {
    try {
        qrDataUrl.value      = await generateQRWithLogo(props.session.license_plate, 170);
        qrDataUrlPrint.value = await generateQRWithLogo(props.session.license_plate, 115);
    } catch (e) {
        console.error('QR generation failed', e);
    }
});
</script>

<template>
    <Head :title="`Ticket - ${session.license_plate}`" />

    <!-- Boutons d'action (masqués à l'impression) -->
    <div class="no-print min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6 gap-4">
        <div class="flex flex-wrap justify-center gap-3 mb-2">
            <Link
                :href="indexRoute"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl text-sm font-semibold transition-all shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour à la liste
            </Link>
            <button
                @click="printTicket"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#143f85] hover:bg-[#0f2a5a] text-white rounded-xl text-sm font-bold shadow-md transition-all active:scale-95"
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

        <!-- Ticket physique (écran) -->
        <div class="ticket-wrapper bg-white rounded-2xl shadow-xl overflow-visible" style="width:380px;">

            <!-- En-tête orange -->
            <div class="ticket-header bg-[#143f85] text-white text-center px-6 py-5 rounded-t-2xl">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2"/>
                    </svg>
                    <span class="text-lg font-black tracking-wide uppercase">Ticket de Stationnement</span>
                </div>
                <p class="text-orange-100 text-xs">Conservez ce ticket jusqu'à votre sortie</p>
            </div>

            <!-- Séparateur dents de scie -->
            <div class="relative bg-white" style="height:20px; overflow:visible;">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="border-t-2 border-dashed border-gray-200 mx-5 mt-2.5"></div>
            </div>

            <!-- Plaque d'immatriculation -->
            <div class="px-6 pt-2 pb-4 text-center">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-2">Immatriculation</p>
                <div class="inline-block border-4 border-gray-800 rounded-xl px-3 py-3 bg-yellow-50">
                    <span class="text-3xl font-black text-gray-900 tracking-[0.25em] uppercase">
                        {{ session.license_plate }}
                    </span>
                </div>
                <p v-if="session.marque || session.modele" class="mt-2.5 text-sm text-gray-600 font-semibold">
                    {{ [session.marque, session.modele].filter(Boolean).join(' ') }}
                </p>
            </div>

            <!-- QR Code avec logo "P" -->
            <div class="px-6 pb-4 flex flex-col items-center gap-1.5">
                <div class="relative">
                    <img
                        v-if="qrDataUrl"
                        :src="qrDataUrl"
                        alt="QR Code"
                        class="w-[170px] h-[170px] rounded-lg shadow-sm"
                    />
                    <!-- Placeholder animé pendant le chargement -->
                    <div v-else class="w-[170px] h-[170px] rounded-lg bg-gray-100 animate-pulse flex items-center justify-center">
                        <span class="text-gray-300 text-xs">Génération…</span>
                    </div>
                </div>
                <p class="text-[10px] text-gray-400 font-medium">Scannez ce QR à la sortie</p>
            </div>

            <!-- Séparateur -->
            <div class="relative bg-white" style="height:20px; overflow:visible;">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-100"></div>
                <div class="border-t-2 border-dashed border-gray-200 mx-5 mt-2.5"></div>
            </div>

            <!-- Informations — label fixe + valeur qui wrappé -->
            <div class="px-6 py-5 space-y-3">
                <!-- Parking -->
                <div class="flex items-start gap-3">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide whitespace-nowrap mt-0.5 w-20 shrink-0">Parking</span>
                    <span class="text-sm font-bold text-gray-900 break-words min-w-0 flex-1 text-right leading-snug">{{ session.parking_name || '—' }}</span>
                </div>
                <!-- Référence -->
                <div v-if="session.parking_ref" class="flex items-start gap-3">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide whitespace-nowrap mt-0.5 w-20 shrink-0">Référence</span>
                    <span class="text-sm font-semibold text-gray-700 break-words min-w-0 flex-1 text-right leading-snug">{{ session.parking_ref }}</span>
                </div>
                <!-- Entrée -->
                <div class="flex items-start gap-3">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide whitespace-nowrap mt-0.5 w-20 shrink-0">Entrée</span>
                    <span class="text-sm font-bold text-orange-600 break-words min-w-0 flex-1 text-right leading-snug">{{ formatDate(session.started_at) }}</span>
                </div>
                <!-- Agent -->
                <div v-if="session.agent_name" class="flex items-start gap-3">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide whitespace-nowrap mt-0.5 w-20 shrink-0">Agent</span>
                    <span class="text-sm text-gray-700 break-words min-w-0 flex-1 text-right leading-snug">{{ session.agent_name }}</span>
                </div>
                <!-- N° Ticket -->
                <div class="flex items-start gap-3 pt-2 border-t border-gray-100">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wide whitespace-nowrap mt-0.5 w-20 shrink-0">N° Ticket</span>
                    <span class="text-xs font-mono font-bold text-gray-500 break-words min-w-0 flex-1 text-right leading-snug">#{{ String(session.id).padStart(6, '0') }}</span>
                </div>
            </div>

            <!-- Pied du ticket -->
            <div class="bg-orange-50 border-t-2 border-dashed border-orange-100 px-6 py-4 text-center rounded-b-2xl">
                <p class="text-xs text-orange-500 font-semibold">Présentez ce ticket à la sortie.</p>
                <p class="text-[10px] text-gray-400 mt-1">Le tarif sera calculé à votre sortie.</p>
            </div>
        </div>
    </div>

    <!-- Version impression uniquement (thermique 80mm) -->
    <div class="print-only" style="display:none;">
        <div style="width:76mm; font-family:Arial, sans-serif; font-size:11px; padding:3mm 4mm;">

            <!-- En-tête -->
            <div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:3mm; margin-bottom:3mm;">
                <div style="font-size:14px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">TICKET DE STATIONNEMENT</div>
                <div style="font-size:9px; color:#555; margin-top:1mm;">Conservez ce ticket jusqu'à votre sortie</div>
            </div>

            <!-- Immatriculation -->
            <div style="text-align:center; margin:3mm 0;">
                <div style="font-size:9px; letter-spacing:2px; color:#555; margin-bottom:2mm; text-transform:uppercase;">Immatriculation</div>
                <div style="font-size:20px; font-weight:900; letter-spacing:4px; border:2px solid #000; display:inline-block; padding:1.5mm 5mm; text-transform:uppercase;">
                    {{ session.license_plate }}
                </div>
                <div v-if="session.marque || session.modele" style="font-size:11px; font-weight:600; margin-top:2mm;">
                    {{ [session.marque, session.modele].filter(Boolean).join(' ') }}
                </div>
            </div>

            <!-- QR code avec logo "P" -->
            <div v-if="qrDataUrlPrint" style="text-align:center; margin:3mm 0;">
                <img :src="qrDataUrlPrint" alt="QR" style="width:115px; height:115px; display:inline-block;" />
                <div style="font-size:8px; color:#777; margin-top:1mm;">Scanner pour sortie rapide</div>
            </div>

            <!-- Ligne de séparation -->
            <div style="border-top:1px dashed #000; margin:3mm 0;"></div>

            <!-- Informations détaillées -->
            <table style="width:100%; font-size:11px; border-collapse:collapse;">
                <tr>
                    <td style="color:#555; padding:0.8mm 0; vertical-align:top; white-space:nowrap; width:28mm;">Parking :</td>
                    <td style="font-weight:700; padding:0.8mm 0; vertical-align:top; text-align:right; word-break:break-word;">{{ session.parking_name || '—' }}</td>
                </tr>
                <tr v-if="session.parking_ref">
                    <td style="color:#555; padding:0.8mm 0; vertical-align:top; white-space:nowrap;">Référence :</td>
                    <td style="padding:0.8mm 0; vertical-align:top; text-align:right;">{{ session.parking_ref }}</td>
                </tr>
                <tr>
                    <td style="color:#555; padding:0.8mm 0; vertical-align:top; white-space:nowrap;">Entrée :</td>
                    <td style="font-weight:700; padding:0.8mm 0; vertical-align:top; text-align:right;">{{ formatDate(session.started_at) }}</td>
                </tr>
                <tr v-if="session.agent_name">
                    <td style="color:#555; padding:0.8mm 0; vertical-align:top; white-space:nowrap;">Agent :</td>
                    <td style="padding:0.8mm 0; vertical-align:top; text-align:right; word-break:break-word;">{{ session.agent_name }}</td>
                </tr>
                <tr>
                    <td style="color:#555; padding:0.8mm 0; vertical-align:top; white-space:nowrap;">N° Ticket :</td>
                    <td style="font-family:monospace; font-weight:700; padding:0.8mm 0; vertical-align:top; text-align:right;">#{{ String(session.id).padStart(6, '0') }}</td>
                </tr>
            </table>

            <!-- Pied -->
            <div style="border-top:1px dashed #000; margin-top:3mm; padding-top:2mm; text-align:center; font-size:9px; color:#555;">
                Présentez ce ticket à la sortie — Tarif calculé à la sortie
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    .no-print  { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; margin: 0; padding: 0; }
    @page { margin: 0; size: 80mm auto; }
}
@media screen {
    .print-only { display: none !important; }
}
</style>
