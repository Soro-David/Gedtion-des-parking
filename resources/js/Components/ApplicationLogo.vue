<template>
    <!--
        ApplicationLogo — Marque "P" Plateau Park
        Monochrome : utilise fill="currentColor" pour s'adapter à n'importe quel contexte.
        Sidebar  : text-white  → P blanc sur fond vert
        GuestLayout : text-[#4A1725] → P bordeaux sur fond clair
    -->
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="none" aria-label="Plateau Park logo">

        <!-- Lettre P avec counter (trou intérieur) via fill-rule evenodd -->
        <!--
            Chemin extérieur CW :
              M 31 8   → coin supérieur-gauche de la hampe
              L 45 8   → haut de la hampe / connexion bol
              A 25 25 0 0 1 45 58  → demi-cercle droit (bol extérieur), r=25, centre (45,33)
              L 45 92  → bas droit de la hampe (sous le bol)
              L 31 92  → bas gauche
              Z        → remonter à (31,8) = côté gauche
            Chemin counter CW (crée le trou avec evenodd) :
              M 45 20  → haut du counter
              A 13 13 0 0 1 45 46  → demi-cercle droit, r=13, centre (45,33)
              Z        → ligne implicite (45,46)→(45,20) = bord plat gauche du trou
        -->
        <path
            fill-rule="evenodd"
            fill="currentColor"
            d="M 31 8 L 45 8 A 25 25 0 0 1 45 58 L 45 92 L 31 92 Z
               M 45 20 A 13 13 0 0 1 45 46 Z"
        />

        <!-- Barre de base (plateau) -->
        <rect x="22" y="95" width="56" height="4" rx="2" fill="currentColor" opacity="0.4" />

    </svg>
</template>
