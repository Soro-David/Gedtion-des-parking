<script setup>
import { computed, onMounted, ref, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);
const showPassword = ref(false);
const attrs = useAttrs();

const isPasswordField = computed(() => attrs.type === 'password');

const inputAttributes = computed(() => {
    const { type, class: className, ...rest } = attrs;
    return rest;
});

const inputType = computed(() => {
    if (!isPasswordField.value) {
        return attrs.type;
    }

    return showPassword.value ? 'text' : 'password';
});

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="relative">
        <input
            :type="inputType"
            v-bind="inputAttributes"
            :class="['input-premium', attrs.class, isPasswordField ? 'pr-11' : '']"
            v-model="model"
            ref="input"
        />

        <button
            v-if="isPasswordField"
            type="button"
            class="absolute inset-y-0 right-3 my-auto text-gray-500 hover:text-gray-700 focus:outline-none"
            :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
            @click="showPassword = !showPassword"
        >
            <svg
                v-if="!showPassword"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"
                />
            </svg>

            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.964 9.964 0 012.202-3.592M6.228 6.228A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.042 5.136M15 12a3 3 0 00-3-3m0 6a3 3 0 01-3-3m0 0a3 3 0 013-3m0 3L3 3m9 9 9 9"
                />
            </svg>
        </button>
    </div>
</template>
    