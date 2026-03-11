<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    announcements: Array
});

const showModal = ref(false);
const form = useForm({
    title: '',
    content: '',
    type: 'news',
    expires_at: ''
});

const submit = () => {
    form.post(route('announcements.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

const deleteAnnouncement = (id) => {
    if (confirm('Are you sure you want to remove this announcement?')) {
        form.delete(route('announcements.destroy', id));
    }
};

const user = computed(() => usePage().props.auth.user);
const canManage = computed(() => {
    return user.value.roles.some(r => ['Super Admin', 'Admin', 'HR'].includes(r));
});

const getTypeStyles = (type) => {
    return {
        'news': 'bg-blue-50 text-blue-700 border-blue-100',
        'alert': 'bg-rose-50 text-rose-700 border-rose-100 animate-pulse',
        'event': 'bg-amber-50 text-amber-700 border-amber-100',
    }[type] || 'bg-gray-50 text-gray-700 border-gray-100';
};

const getTypeIcon = (type) => {
    return { 'news': '📢', 'alert': '🚨', 'event': '📅' }[type] || '📝';
};
</script>

<template>
    <Head title="Office Announcements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Office Announcements</h2>
                <button v-if="canManage" @click="showModal = true" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-2xl font-black uppercase text-xs shadow-md active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    New Broadcasting
                </button>
            </div>
        </template>

        <div class="py-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <div v-for="item in announcements" :key="item.id" 
                     class="bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-8">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">{{ getTypeIcon(item.type) }}</span>
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">{{ item.title }}</h3>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                        Posted by {{ item.user.name }} • {{ new Date(item.created_at).toLocaleDateString('en-IN', {day:'numeric', month:'short'}) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase border shadow-sm" :class="getTypeStyles(item.type)">
                                    {{ item.type }}
                                </span>
                                <button v-if="canManage" @click="deleteAnnouncement(item.id)" class="p-2 text-gray-400 hover:text-rose-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed font-medium whitespace-pre-wrap">{{ item.content }}</p>
                    </div>
                </div>

                <div v-if="announcements.length === 0" class="text-center py-20 bg-gray-50 rounded-[3rem] border-2 border-dashed border-gray-200">
                    <span class="text-6xl mb-4 block">🤫</span>
                    <p class="text-gray-400 font-black uppercase tracking-widest text-sm">No active announcements</p>
                </div>

            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity" @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <form @submit.prevent="submit">
                        <div class="bg-white p-8">
                            <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase tracking-tighter">Global Broadcast</h3>
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Title</label>
                                    <input v-model="form.title" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold shadow-inner" placeholder="e.g. Office Picnic this Weekend" required />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Post Type</label>
                                        <select v-model="form.type" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold shadow-inner">
                                            <option value="news">📢 General News</option>
                                            <option value="alert">🚨 Urgent Alert</option>
                                            <option value="event">📅 Team Event</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Expiry Date</label>
                                        <input v-model="form.expires_at" type="date" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold shadow-inner" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Message Content</label>
                                    <textarea v-model="form.content" rows="5" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold shadow-inner" placeholder="Write your message here..." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-8 flex gap-4">
                            <button type="submit" :disabled="form.processing" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-2xl font-black uppercase text-sm shadow-lg transition-all disabled:opacity-50">
                                {{ form.processing ? 'Broadcasting...' : '🚀 Blast to Office' }}
                            </button>
                            <button type="button" @click="showModal = false" class="px-8 bg-white border border-gray-200 text-gray-600 py-3 rounded-2xl font-black uppercase text-sm hover:bg-gray-100 transition-all text-center">
                                Close
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
