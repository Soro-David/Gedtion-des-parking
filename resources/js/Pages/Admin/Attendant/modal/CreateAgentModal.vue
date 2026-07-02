<script setup>
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'submit']);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const close = () => emit('close');
const submit = () => emit('submit');
</script>

<template>
    <Modal :show="props.show" @close="close">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Créer un Superviseur</h3>

            <div class="grid grid-cols-1 gap-4">
                <input v-model="props.form.name" placeholder="Nom" class="border p-3 rounded" />
                <input v-model="props.form.email" placeholder="Email" class="border p-3 rounded" />
                <div class="relative">
                    <input
                        v-model="props.form.password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Mot de passe"
                        class="border p-3 rounded w-full pr-11"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-3 my-auto text-gray-500 hover:text-gray-700"
                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.964 9.964 0 012.202-3.592M6.228 6.228A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.042 5.136M15 12a3 3 0 00-3-3m0 6a3 3 0 01-3-3m0 0a3 3 0 013-3m0 3L3 3m9 9 9 9" />
                        </svg>
                    </button>
                </div>
                <div class="relative">
                    <input
                        v-model="props.form.password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        placeholder="Confirmer le mot de passe"
                        class="border p-3 rounded w-full pr-11"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-3 my-auto text-gray-500 hover:text-gray-700"
                        :aria-label="showPasswordConfirmation ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                    >
                        <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.964 9.964 0 012.202-3.592M6.228 6.228A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.042 5.136M15 12a3 3 0 00-3-3m0 6a3 3 0 01-3-3m0 0a3 3 0 013-3m0 3L3 3m9 9 9 9" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button @click="close" class="px-4 py-2 bg-[#787878] text-white hover:bg-white hover:text-[#787878] 
                        border border-[#787878] rounded border">Annuler</button>
                <!-- <button @click="submit" class="px-4 py-2 rounded bg-[#4A1725] text-white">Créer</button> -->

                <button
                    @click="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 
                        bg-[#143f85] text-white 
                        hover:bg-white hover:text-[#143f85] 
                        border border-[#143f85] 
                        text-sm font-semibold rounded-xl 
                        shadow-md shadow-[#143f85]/20 
                        transition-all duration-200 
                        hover:-translate-y-0.5 active:scale-95 
                        whitespace-nowrap"
                >
                Enregistrer
                </button>
            </div>
        </div>
    </Modal>
</template>
