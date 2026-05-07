<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

defineEmits(['toggle-sidebar']);
</script>

<template>
    <nav class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b-white/20 px-4 glass-card sm:px-6">
        <div class="flex items-center">
            <!-- Menu Toggle -->
            <button
                @click="$emit('toggle-sidebar')"
                class="mr-2 rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <h1 class="hidden text-xl font-semibold text-gray-800 sm:block">
                <slot name="headerText" />
            </h1>
        </div>

        <div class="flex items-center space-x-4">
            <!-- Notifications (Placeholder) -->
            <button class="p-2 text-gray-400 transition-colors hover:text-[#4A1725]">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>

            <!-- User Menu -->
            <div class="relative">
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="flex items-center space-x-2 rounded-full p-1 transition-colors hover:bg-gray-100 focus:outline-none">
                            <span class="hidden text-sm font-medium text-gray-700 md:block">{{ user.name }}</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-[#4A1725] font-semibold text-white">
                                {{ user.name.charAt(0) }}
                            </div>
                        </button>
                    </template>

                    <template #content>
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ user.email }}</p>
                        </div>
                        <DropdownLink :href="route('profile.edit')"> Profil </DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            Déconnexion
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>
    </nav>
</template>
