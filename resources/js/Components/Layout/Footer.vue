<script setup>
import { Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="bg-white border-t border-gray-100 py-8 px-6 mt-auto">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <!-- Brand & Copyright -->
                <div class="flex flex-col items-center">
                    <p class="text-gray-400">
                        &copy; Tous droits réservés.
                    </p>
                </div>


                <!-- Status / Info -->
                <div class="flex items-center gap-4">
                    <div class="flex bg-50 rounded-full">
                        <span class="text-[10px] font-bold text-600 uppercase tracking-widest">Développé par KKS-TECHNOLOGIES</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
