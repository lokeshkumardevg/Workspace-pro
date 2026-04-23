<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const input = ref('');
const messages = ref([
    { role: 'assistant', content: 'Hi! I am **Wheedle Brain** 🧠\nHow can I help you to manage your work today?' }
]);
const isTyping = ref(false);
const chatContainer = ref(null);

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        scrollToBottom();
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const parseMarkdown = (text) => {
    if (!text) return '';
    let parsed = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    parsed = parsed.replace(/\*(.*?)\*/g, '<em>$1</em>');
    parsed = parsed.replace(/\n/g, '<br/>');
    return parsed;
};

const sendMessage = async () => {
    if (!input.value.trim() || isTyping.value) return;

    const userText = input.value;
    messages.value.push({ role: 'user', content: userText });
    input.value = '';
    isTyping.value = true;
    scrollToBottom();

    try {
        const response = await axios.post(route('ai.chat'), {
            messages: messages.value.map(m => ({ role: m.role, content: m.content }))
        });

        const reply = response.data.message;
        if (reply && reply.content) {
            messages.value.push({ role: 'assistant', content: reply.content });
        } else {
            messages.value.push({ role: 'assistant', content: '*Action performed successfully.*' });
        }
    } catch (error) {
        console.error(error);
        messages.value.push({ role: 'assistant', content: 'Sorry, I encountered an error connecting to my brain. Please try again later.' });
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

onMounted(() => {
    // Escape key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen.value) {
            isOpen.value = false;
        }
    });
});
</script>

<template>
    <div>
        <!-- Floating Action Button -->
        <button 
            @click="toggleChat"
            class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-gradient-to-tr from-indigo-600 to-purple-600 text-white rounded-full shadow-2xl hover:scale-110 hover:shadow-purple-500/50 transition-all duration-300 flex items-center justify-center group focus:outline-none focus:ring-4 focus:ring-purple-300"
            :class="{ 'rotate-180 scale-90': isOpen }"
        >
            <svg v-if="!isOpen" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Chat Widget -->
        <transition name="chat-slide">
            <div v-show="isOpen" class="fixed bottom-24 right-6 w-[22rem] sm:w-[24rem] h-[32rem] bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden z-50">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-900 to-purple-900 p-4 shrink-0 flex items-center justify-between shadow-md relative overflow-hidden">
                    <!-- Glass effect overlay -->
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center p-1.5 shadow-inner">
                            <span class="text-xl">🧠</span>
                        </div>
                        <div>
                            <h3 class="text-white font-black text-sm uppercase tracking-widest">Wheedle Brain</h3>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                                <span class="text-[9px] text-indigo-200 font-bold tracking-wider">AI AGENT ONLINE</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Area -->
                <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50 scrollbar-thin">
                    <template v-for="(msg, index) in messages" :key="index">
                        
                        <!-- Assistant Message -->
                        <div v-if="msg.role === 'assistant'" class="flex items-start gap-2 max-w-[85%]">
                            <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 mt-1 shadow-sm border border-indigo-200">
                                <span class="text-xs">🧠</span>
                            </div>
                            <div class="bg-white border border-gray-100 shadow-sm p-3 rounded-2xl rounded-tl-sm text-[13px] text-gray-700 leading-relaxed font-medium" 
                                 v-html="parseMarkdown(msg.content)">
                            </div>
                        </div>

                        <!-- User Message -->
                        <div v-else class="flex items-start justify-end gap-2 max-w-[85%] ml-auto">
                            <div class="bg-indigo-600 shadow-sm p-3 rounded-2xl rounded-tr-sm text-[13px] text-white leading-relaxed font-medium">
                                {{ msg.content }}
                            </div>
                        </div>

                    </template>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="flex items-start gap-2 max-w-[85%]">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 mt-1 shadow-sm">
                            <span class="text-xs">🧠</span>
                        </div>
                        <div class="bg-white border border-gray-100 shadow-sm px-4 py-3.5 rounded-2xl rounded-tl-sm flex gap-1 items-center">
                            <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                            <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                            <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce"></div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-3 bg-white border-t border-gray-100 shrink-0">
                    <form @submit.prevent="sendMessage" class="relative flex items-center">
                        <input 
                            v-model="input" 
                            type="text" 
                            placeholder="Message AI directly..." 
                            class="w-full bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 rounded-xl py-3 pl-4 pr-12 text-sm font-medium shadow-inner transition-all placeholder:text-gray-300"
                            :disabled="isTyping"
                        />
                        <button type="submit" :disabled="!input.trim() || isTyping" 
                                class="absolute right-2 p-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50 disabled:bg-gray-300 hover:bg-indigo-700 transition-colors focus:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </form>
                    <div class="text-center mt-2">
                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-widest">Powered by GPT-4o Model</p>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.chat-slide-enter-active,
.chat-slide-leave-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.chat-slide-enter-from,
.chat-slide-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    transform-origin: bottom right;
}
</style>
