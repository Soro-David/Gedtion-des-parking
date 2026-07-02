<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>

<template>
    <Head title="Politique de confidentialité – Gespark" />
    <PublicLayout>

        <!-- ── Main Content Area ───────────────────────────────────── -->
        <div class="bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                
                <!-- Simple Document Title -->
                <h1 class="text-2xl font-bold text-slate-900 mb-10 border-b border-slate-200 pb-4">
                    Politique de confidentialité
                </h1>

                <!-- Main Text Content (Single Column) -->
                <main class="space-y-12">
                    
                    <!-- Section: Introduction -->
                    <section id="intro">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            1. Introduction & Responsabilité
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                La présente Politique de Confidentialité a pour but d'informer les utilisateurs de la plateforme <strong>Gespark</strong> (site internet et application mobile) des conditions dans lesquelles leurs données à caractère personnel sont collectées, traitées, stockées et protégées.
                            </p>
                            <p>
                                Gespark est développée et éditée par <strong>KKS Technologies</strong>. En accédant et en utilisant nos services (gestion de parkings en temps réel, OCR de plaques, suivi d'occupation, etc.), vous acceptez les pratiques décrites dans ce document.
                            </p>
                        </div>
                    </section>

                    <!-- Section: Données collectées -->
                    <section id="collecte">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            2. Données collectées
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                Nous collectons uniquement les informations nécessaires au bon fonctionnement et à l'amélioration de notre service de gestion de parking. Ces données incluent :
                            </p>
                            <ul class="space-y-2 pl-4 list-disc">
                                <li>
                                    <strong>Données d'identification de compte :</strong> Nom, adresse e-mail, rôle applicatif (Superadmin, Administrateur, Superviseur, Caissier, Agent) et informations de connexion.
                                </li>
                                <li>
                                    <strong>Données de stationnement :</strong> Référence des parkings, heures d'entrée et de sortie, durée de stationnement, plaque d'immatriculation et montant payé ou à payer.
                                </li>
                                <li>
                                    <strong>Données de paiement & de versement :</strong> Historique des encaissements effectués par les caissiers, rapports de reversements aux administrateurs et suivis financiers.
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Utilisation des données -->
                    <section id="usage">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            3. Utilisation des données
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                Les données collectées sont utilisées exclusivement pour les finalités suivantes :
                            </p>
                            <ul class="space-y-2 pl-4 list-disc">
                                <li>
                                    <strong>Gestion opérationnelle :</strong> Calcul du taux d'occupation des zones de parking, contrôle des accès et enregistrement des encaissements en temps réel.
                                </li>
                                <li>
                                    <strong>Sécurisation financière :</strong> Suivi des flux de versement et de reversement entre agents de terrain, caissiers et administrateurs.
                                </li>
                                <li>
                                    <strong>Tableaux de bord & statistiques :</strong> Génération de rapports analytiques pour aider les gestionnaires à optimiser leurs espaces et leurs tarifs.
                                </li>
                                <li>
                                    <strong>Support & Maintenance :</strong> Résolution des incidents techniques et amélioration continue de l'expérience utilisateur.
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: OCR & Données géolocalisées -->
                    <section id="ocr-geo">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            4. OCR & Données géolocalisées
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                Gespark intègre des technologies avancées pour simplifier le travail des agents et des conducteurs :
                            </p>
                            <ul class="space-y-3 pl-4 list-disc">
                                <li>
                                    <strong>Reconnaissance optique de caractères (OCR) :</strong> Lorsqu'un agent ou caissier scanne une plaque d'immatriculation, la photo est traitée temporairement pour en extraire le texte. Les photos d'origine ne sont pas conservées au-delà de la session de stationnement active.
                                </li>
                                <li>
                                    <strong>Données de géolocalisation :</strong> La position des parkings est enregistrée pour la cartographie. La position géographique des agents mobiles peut être lue au moment de l'enregistrement de l'entrée/sortie pour authentifier le lieu de l'opération.
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Sécurité -->
                    <section id="security">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            5. Sécurité & Protection
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                La sécurité de vos données est une priorité absolue. <strong>KKS Technologies</strong> met en œuvre des mesures techniques et organisationnelles rigoureuses :
                            </p>
                            <ul class="space-y-2 pl-4 list-disc">
                                <li>
                                    <strong>Mots de passe :</strong> Tous les mots de passe sont hachés de manière sécurisée (BCrypt) et ne sont jamais stockés en clair.
                                </li>
                                <li>
                                    <strong>Chiffrement :</strong> Les communications entre nos applications et les serveurs sont chiffrées via HTTPS / SSL.
                                </li>
                                <li>
                                    <strong>Contrôle d'accès :</strong> Chaque utilisateur n'accède qu'aux données strictement liées à son rôle (RBAC).
                                </li>
                            </ul>
                            <p>
                                Aucune donnée personnelle n'est vendue, louée ou cédée à des tiers à des fins marketing.
                            </p>
                        </div>
                    </section>

                    <!-- Section: Vos droits -->
                    <section id="rights">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            6. Vos droits & Choix
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                Conformément à la réglementation sur la protection des données personnelles, vous disposez des droits suivants :
                            </p>
                            <ul class="space-y-2 pl-4 list-disc">
                                <li><strong>Droit d'accès :</strong> Obtenir la confirmation que vos données sont traitées.</li>
                                <li><strong>Droit de rectification :</strong> Corriger vos informations si elles sont inexactes.</li>
                                <li><strong>Droit à la suppression :</strong> Demander l'effacement définitif de votre compte.</li>
                                <li><strong>Droit d'opposition :</strong> Refuser le traitement de vos données pour motifs légitimes.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Contact -->
                    <section id="contact" class="pb-16">
                        <h2 class="text-base font-bold text-slate-900 mb-3">
                            7. Support & Contact
                        </h2>
                        <div class="text-sm text-slate-600 leading-relaxed space-y-4">
                            <p>
                                Pour toute question concernant cette politique ou pour exercer vos droits, vous pouvez contacter notre équipe :
                            </p>
                            <p class="text-slate-800 font-semibold">
                                KKS Technologies Support<br>
                                Service Protection des Données<br>
                                Abidjan, Côte d'Ivoire
                            </p>
                            <div class="pt-2">
                                <Link :href="route('public.contact')" class="text-xs font-bold text-slate-900 underline hover:text-slate-600 transition-colors">
                                    Nous contacter par e-mail
                                </Link>
                            </div>
                        </div>
                    </section>

                </main>

            </div>
        </div>

    </PublicLayout>
</template>
