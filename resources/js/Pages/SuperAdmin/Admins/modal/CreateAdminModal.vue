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
            <h3 class="text-lg font-semibold mb-4">Créer un Admin</h3>

            <div class="grid grid-cols-1 gap-4">
                <input v-model="props.form.name" placeholder="Nom" class="border p-3 rounded" />
                <input v-model="props.form.email" placeholder="Email" class="border p-3 rounded" />
                <input v-model="props.form.password" type="password" placeholder="Password" class="border p-3 rounded" />
                <input v-model="props.form.password_confirmation" type="password" placeholder="Confirm Password" class="border p-3 rounded" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button @click="close" class="px-4 py-2 rounded border">Annuler</button>
                <button @click="submit" class="px-4 py-2 rounded bg-[#487119] text-white">Créer</button>
            </div>
        </div>
    </Modal>
</template>
