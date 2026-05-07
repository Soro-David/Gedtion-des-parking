<script setup>
/**
 * ParkingAiAssistant
 * Widget flottant « ParkBot » propulsé par Gemini AI.
 * Se glisse dans n'importe quelle page via AuthenticatedLayout.
 */
import { ref, nextTick } from 'vue';
import axios from 'axios';

const open        = ref(false);
const input       = ref('');
const loading     = ref(false);
const chatEl      = ref(null);

const messages = ref([
    { role: 'assistant', text: 'Bonjour ! Je suis ParkBot 🅿️, votre assistant parking. Posez-moi vos questions sur les places disponibles, les tarifs ou les emplacements.' },
]);

const history = ref([]); // [{role, text}] pour le contexte Gemini

const scrollToBottom = () => {
    nextTick(() => {
        if (chatEl.value) chatEl.value.scrollTop = chatEl.value.scrollHeight;
    });
};

const sendMessage = async () => {
    const question = input.value.trim();
    if (!question || loading.value) return;

    messages.value.push({ role: 'user', text: question });
    history.value.push({ role: 'user', text: question });
    input.value = '';
    loading.value = true;
    scrollToBottom();

    try {
        const res = await axios.post(route('api.parking-ai.ask'), {
            question,
            history: history.value.slice(-12), // garder les 12 derniers pour le contexte
        });
        const answer = res.data.answer || 'Désolé, je n\'ai pas pu répondre.';
        messages.value.push({ role: 'assistant', text: answer });
        history.value.push({ role: 'assistant', text: answer });
    } catch {
        messages.value.push({ role: 'assistant', text: 'Erreur de connexion à l\'IA. Veuillez réessayer.' });
    } finally {
        loading.value = false;
        scrollToBottom();
    }
};

const onKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
};

// Suggestions rapides
const suggestions = [
    'Quels parkings ont des places disponibles ?',
    'Quel est le tarif le moins cher ?',
    'Comment trouver le parking le plus proche ?',
];

const quickSend = (text) => {
    input.value = text;
    sendMessage();
};
</script>

<template>
    <!-- Bouton flottant -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
        <!-- Fenêtre de chat -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-90 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-90 translate-y-4"
        >
            <div
                v-if="open"
                class="w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden"
                style="max-height: 520px;"
            >
                <!-- Header -->
                <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-[#4A1725] to-[#7c2d3d]">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-white font-black text-sm">🅿</div>
                    <div>
                        <p class="text-white font-bold text-sm leading-none">ParkBot</p>
                        <p class="text-white/60 text-[10px]">Propulsé par Gemini AI</p>
                    </div>
                    <button
                        @click="open = false"
                        class="ml-auto text-white/60 hover:text-white transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Messages -->
                <div ref="chatEl" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-gray-50/50" style="min-height:0">
                    <div
                        v-for="(msg, i) in messages"
                        :key="i"
                        :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
                    >
                        <div
                            :class="[
                                'max-w-[85%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed',
                                msg.role === 'user'
                                    ? 'bg-[#4A1725] text-white rounded-br-sm'
                                    : 'bg-white text-gray-800 border border-gray-100 shadow-sm rounded-bl-sm',
                            ]"
                            style="white-space: pre-wrap;"
                        >{{ msg.text }}</div>
                    </div>

                    <!-- Indicateur de chargement -->
                    <div v-if="loading" class="flex justify-start">
                        <div class="bg-white border border-gray-100 shadow-sm px-4 py-3 rounded-2xl rounded-bl-sm flex gap-1.5">
                            <span class="w-2 h-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                            <span class="w-2 h-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                            <span class="w-2 h-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                        </div>
                    </div>
                </div>

                <!-- Suggestions rapides (seulement au début) -->
                <div v-if="messages.length <= 1" class="px-4 py-2 flex flex-col gap-1.5 border-t border-gray-50">
                    <button
                        v-for="s in suggestions"
                        :key="s"
                        @click="quickSend(s)"
                        class="text-left text-xs text-[#4A1725] font-semibold bg-[#4A1725]/5 hover:bg-[#4A1725]/10 px-3 py-1.5 rounded-lg transition-colors"
                    >{{ s }}</button>
                </div>

                <!-- Zone de saisie -->
                <div class="px-4 py-3 border-t border-gray-100 bg-white">
                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="input"
                            @keydown="onKeydown"
                            placeholder="Posez votre question..."
                            rows="1"
                            class="flex-1 resize-none rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4A1725]/30 focus:border-[#4A1725] transition-colors"
                            style="max-height:80px"
                        ></textarea>
                        <button
                            @click="sendMessage"
                            :disabled="loading || !input.trim()"
                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-[#4A1725] text-white hover:bg-[#3a1020] disabled:opacity-40 transition-colors shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
        
        <!-- Bouton principal -->
        <!-- <button
            @click="open = !open"
            class="w-14 h-14 rounded-full bg-gradient-to-br from-[#4A1725] to-[#7c2d3d] text-white shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-200 flex items-center justify-center relative"
            title="Assistant IA Parking"
        >
            <svg v-if="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>

            <span v-if="!open" class="absolute -top-0.5 -right-0.5 w-3.5 h-3.5 bg-green-400 rounded-full border-2 border-white animate-pulse"></span>
        </button> -->
    </div>
</template>
