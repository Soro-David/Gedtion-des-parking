<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
});

function onlyDigits(field) {
    props.form[field] = (props.form[field] ?? '').replace(/\D/g, '').slice(0, 10);
}
</script>

<template>
    <div class="space-y-8">
        <!-- Identité -->
        <div class="bg-gray-50/50 p-6 rounded-3xl border border-gray-100 space-y-5">
            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                Identité & Accès
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="name" value="Nom" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full !bg-white" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="first_name" value="Prénoms" />
                    <TextInput id="first_name" v-model="form.first_name" type="text" class="mt-1 block w-full !bg-white" required />
                    <InputError :message="form.errors.first_name" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full !bg-white" required />
                    <InputError :message="form.errors.email" />
                </div>
            </div>
        </div>

        <!-- Info invitation -->
        <div class="flex items-start gap-3 px-4 py-3 bg-blue-50 border border-blue-100 rounded-2xl text-sm text-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            Un email d'invitation sera envoyé automatiquement avec un lien pour définir le mot de passe (valable 48h).
        </div>

        <!-- Coordonnées -->
        <div class="bg-gray-50/50 p-6 rounded-3xl border border-gray-100 space-y-5">
            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                Coordonnées
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="phone" value="Contact (Téléphone)" />
                    <TextInput id="phone" v-model="form.phone" type="text" inputmode="numeric" maxlength="10" class="mt-1 block w-full !bg-white" @input="onlyDigits('phone')" />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="address" value="Adresse" />
                    <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full !bg-white" />
                    <InputError :message="form.errors.address" />
                </div>
            </div>
        </div>

        <!-- Urgence -->
        <div class="bg-gray-50/50 p-6 rounded-3xl border border-gray-100 space-y-5">
            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                Contact d'Urgence
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <InputLabel for="emergency_name" value="Nom de la personne à contacter" />
                    <TextInput id="emergency_name" v-model="form.emergency_name" type="text" class="mt-1 block w-full !bg-white" />
                    <InputError :message="form.errors.emergency_name" />
                </div>

                <div>
                    <InputLabel for="emergency_contact" value="Contact d'urgence" />
                    <TextInput id="emergency_contact" v-model="form.emergency_contact" type="text" inputmode="numeric" maxlength="10" class="mt-1 block w-full !bg-white" @input="onlyDigits('emergency_contact')" />
                    <InputError :message="form.errors.emergency_contact" />
                </div>

                <div>
                    <InputLabel for="emergency_relationship" value="Lien de parenté" />
                    <TextInput id="emergency_relationship" v-model="form.emergency_relationship" type="text" class="mt-1 block w-full !bg-white" />
                    <InputError :message="form.errors.emergency_relationship" />
                </div>
            </div>
        </div>
    </div>
</template>
