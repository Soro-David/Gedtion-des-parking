<script setup>
/**
 * PlateauParkLogo
 * Logo complet "Plateau Park" — icône P + texte.
 * Variantes : horizontal (par défaut) | vertical (prop `vertical`)
 */
defineProps({
    vertical: {
        type: Boolean,
        default: false,
    },
    /** Taille de l'icône en px (l'icône est toujours carrée) */
    iconSize: {
        type: Number,
        default: 48,
    },
});
</script>

<template>
    <div
        :class="[
            'inline-flex items-center select-none',
            vertical ? 'flex-col gap-3' : 'flex-row gap-4',
        ]"
    >
        <!-- ── Icône P ── -->
        <div
            class="flex items-center justify-center rounded-2xl bg-[#487119] shadow-lg shadow-[#487119]/40 flex-shrink-0"
            :style="{ width: iconSize + 'px', height: iconSize + 'px', padding: Math.round(iconSize * 0.14) + 'px' }"
        >
            <!-- P mark monochrome blanc -->
            <svg
                viewBox="0 0 100 100"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                class="w-full h-full"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    fill="white"
                    d="M 31 8 L 45 8 A 25 25 0 0 1 45 58 L 45 92 L 31 92 Z
                       M 45 20 A 13 13 0 0 1 45 46 Z"
                />
                <rect x="22" y="95" width="56" height="4" rx="2" fill="white" opacity="0.5" />
            </svg>
        </div>

        <!-- ── Texte du logo ── -->
        <div :class="vertical ? 'text-center' : ''">
            <!-- Ligne principale -->
            <div class="flex items-baseline gap-1 leading-none">
                <span class="font-extrabold tracking-tight text-gray-900" :style="{ fontSize: Math.round(iconSize * 0.52) + 'px' }">
                    Plateau
                </span>
                <span class="font-extrabold tracking-tight text-[#487119]" :style="{ fontSize: Math.round(iconSize * 0.52) + 'px' }">
                    Park
                </span>
                <span class="font-black text-[#487119]" :style="{ fontSize: Math.round(iconSize * 0.6) + 'px', lineHeight: 1 }">.</span>
            </div>
            <!-- Sous-titre discret -->
            <p
                class="text-gray-400 font-medium tracking-widest uppercase"
                :style="{ fontSize: Math.round(iconSize * 0.22) + 'px' }"
            >
                Gestion de stationnement
            </p>
        </div>
    </div>
</template>
