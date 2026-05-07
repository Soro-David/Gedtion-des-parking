<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import UserFormFields from '@/Components/Layout/UserFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import CancelButton from '@/Components/CancelButton.vue';
import ParkingSelector from '@/Components/ParkingSelector.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    caissier: Object,
    parkings: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.caissier.name,
    first_name: props.caissier.first_name,
    email: props.caissier.email,
    phone: props.caissier.phone ?? '',
    address: props.caissier.address ?? '',
    emergency_contact: props.caissier.emergency_contact ?? '',
    emergency_relationship: props.caissier.emergency_relationship ?? '',
    emergency_name: props.caissier.emergency_name ?? '',
    parking_ids: (props.caissier.parkings ?? []).map((p) => p.id),
});

const submit = () => {
    form.put(route('admin.caissier.update', props.caissier.id));
};
</script>

<template>
    <Head title="Modifier le Caissier" />

    <AuthenticatedLayout>
        <template #header>Modifier le Caissier</template>

        <div class="max-w-4xl mx-auto pb-20">
            <div class="flex items-center gap-3 mb-8">
                <Link :href="route('admin.caissier.index')" class="p-2 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200" title="Retour">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tightest">Modifier un Caissier</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Mettez à jour les informations du caissier.</p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <form @submit.prevent="submit" class="p-8 space-y-8">
                    <UserFormFields :form="form" />

                    <!-- Reprogrammation des parkings -->
                    <div>
                        <InputLabel value="Parkings affectés" class="text-sm font-semibold text-gray-700 mb-3" />
                        <ParkingSelector
                            :parkings="parkings"
                            v-model="form.parking_ids"
                            :error="form.errors.parking_ids"
                        />
                    </div>

                    <div class="pt-8 border-t border-gray-100 flex items-center justify-between gap-4">
                        <CancelButton :href="route('admin.caissier.index')" />
                        <PrimaryButton class="px-10 py-4 rounded-[2rem] font-black text-sm" :disabled="form.processing">
                            Enregistrer les modifications
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
