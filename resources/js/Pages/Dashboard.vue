<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Import role dashboards
import AdminDashboard from './Roles/AdminDashboard.vue';
import SupervisorDashboard from './Roles/SupervisorDashboard.vue';
import AttendantDashboard from './Roles/AttendantDashboard.vue';
import DriverDashboard from './Roles/DriverDashboard.vue';

import { ROLE_ADMIN, ROLE_SUPERVISOR, ROLE_ATTENDANT, ROLE_DRIVER, ROLE_LABELS } from '@/Constants/Roles';

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value.role);
const stats = computed(() => page.props.stats ?? {});

const roleDisplayName = computed(() => ROLE_LABELS[role.value] || 'Utilisateur');
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-2">
                <span class="text-[#7E1B1C] font-bold">Tableau de bord</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-500 font-medium">{{ roleDisplayName }}</span>
            </div>
        </template>

        <!-- Dynamic Dashboard based on user role — stats passed down as props -->
        <AdminDashboard      v-if="role === ROLE_ADMIN"       :stats="stats" />
        <SupervisorDashboard v-else-if="role === ROLE_SUPERVISOR" :stats="stats" />
        <AttendantDashboard  v-else-if="role === ROLE_ATTENDANT"  :stats="stats" />
        <DriverDashboard     v-else-if="role === ROLE_DRIVER"     :stats="stats" />

        <div v-else class="p-8 bg-white rounded-2xl shadow-sm border border-gray-100 text-center">
            <h3 class="text-xl font-bold text-gray-800">Accès restreint</h3>
            <p class="text-gray-500 mt-2">Votre rôle ({{ role }}) n'a pas encore de tableau de bord configuré.</p>
        </div>
    </AuthenticatedLayout>
</template>
