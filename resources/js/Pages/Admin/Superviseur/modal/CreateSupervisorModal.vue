<script setup>
import Modal from '@/Components/Modal.vue';

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
                <input v-model="props.form.password" type="password" placeholder="Mot de passe" class="border p-3 rounded" />
                <input v-model="props.form.password_confirmation" type="password" placeholder="Confirmer le mot de passe" class="border p-3 rounded" />
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
