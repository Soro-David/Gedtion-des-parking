<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import Navbar from '@/Components/Layout/Navbar.vue';
import Footer from '@/Components/Layout/Footer.vue';
import ParkingAiAssistant from '@/Components/ParkingAiAssistant.vue';

const isMobileSidebarOpen = ref(false);
const isCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true');

const toggleSidebar = () => {
    if (window.innerWidth < 640) {
        isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
    } else {
        isCollapsed.value = !isCollapsed.value;
        localStorage.setItem('sidebar-collapsed', isCollapsed.value);
    }
};
</script>

<template>
    <div class="flex min-h-screen bg-[#f8fafc]">
        <!-- Sidebar Backdrop (Mobile only) -->
        <div 
            v-if="isMobileSidebarOpen"
            @click="isMobileSidebarOpen = false"
            class="fixed inset-0 z-30 transition-opacity bg-gray-900/50 backdrop-blur-sm sm:hidden"
        ></div>

        <!-- Sidebar -->
        <Sidebar 
            :is-open="isMobileSidebarOpen" 
            :is-collapsed="isCollapsed"
            @toggle="toggleSidebar" 
        />

        <!-- Main Content Area -->
        <div 
            :class="[
                'flex flex-col flex-grow min-h-screen min-w-0 transition-all duration-300',
                isCollapsed ? 'sm:pl-20' : 'sm:pl-64'
            ]"
        >
            <!-- Navbar -->
            <Navbar @toggle-sidebar="toggleSidebar">
                <template #headerText v-if="$slots.header" :is-collapsed="isCollapsed">
                    <slot name="header" />
                </template>
            </Navbar>

            <!-- Page Content -->
            <main class="flex-grow p-6">
                <!-- Message Flash / Notifications system could go here -->
                <div class="animate-fade-in">
                    <slot />
                </div>
            </main>
            <Footer />
        </div>

        <!-- Assistant IA ParkBot (Gemini) -->
        <ParkingAiAssistant />
    </div>
</template>

<style scoped>
/* Redundant animation removed as it is now in global app.css */
</style>
