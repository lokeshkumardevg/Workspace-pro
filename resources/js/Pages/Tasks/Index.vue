<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, reactive, nextTick } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    tasks: Object,
    projects: Array,
    users: Array,
    performance: Number,
    performanceLabel: String,
    filters: Object,
});

const filter = reactive({
    search: props.filters?.search || '',
    filter_type: props.filters?.filter_type || 'all',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const updateFilters = debounce(() => {
    router.get(route('tasks.index'), filter, { preserveState: true, replace: true });
}, 300);

watch(() => filter.search, updateFilters);
watch(() => filter.filter_type, () => {
    if (filter.filter_type !== 'custom') {
        filter.start_date = '';
        filter.end_date = '';
    }
    updateFilters();
});
watch(() => filter.start_date, updateFilters);
watch(() => filter.end_date, updateFilters);

const exportTasks = () => {
    const params = new URLSearchParams(filter).toString();
    window.location.href = route('tasks.export') + '?' + params;
};

// Create Task Modal
const showCreateModal = ref(false);
const form = useForm({
    project_id: '',
    assigned_to: '',
    title: '',
    description: '',
    due_date: '',
});

const createTask = () => {
    form.post(route('tasks.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const updateStatus = (taskId, status) => {
    router.put(route('tasks.status', taskId), { status: status }, { preserveScroll: true });
};

// Comments / Communication Desk
const selectedTask = ref(null);
const showCommunicationDesk = ref(false);
const commentScrollArea = ref(null);

const openCommunication = (task) => {
    selectedTask.value = task;
    showCommunicationDesk.value = true;
    nextTick(() => {
        scrollToBottom();
    });
};

const scrollToBottom = () => {
    if (commentScrollArea.value) {
        commentScrollArea.value.scrollTop = commentScrollArea.value.scrollHeight;
    }
};

const commentForm = useForm({
    comment: '',
    attachment: null,
});

const postComment = () => {
    commentForm.post(route('tasks.comments.store', selectedTask.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            // Find updated task in list to update comments locally if needed, 
            // but Inertia reloads props so selectedTask.value.comments might need refresh
            const updatedTask = props.tasks.data.find(t => t.id === selectedTask.value.id);
            if (updatedTask) selectedTask.value = updatedTask;
            nextTick(() => scrollToBottom());
        }
    });
};

const handleFile = (e) => {
    commentForm.attachment = e.target.files[0];
};

// Reassign Task
const showReassignModal = ref(false);
const reassignForm = useForm({
    assigned_to: '',
});

const openReassignModal = (task) => {
    selectedTask.value = task;
    reassignForm.assigned_to = task.assigned_to;
    showReassignModal.value = true;
};

const submitReassign = () => {
    reassignForm.put(route('tasks.reassign', selectedTask.value.id), {
        onSuccess: () => {
            showReassignModal.value = false;
        }
    });
};
</script>

<template>
    <Head title="Tasks Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6">
                <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                    Task Tracking & Performance
                </h2>

                <div class="flex items-center gap-4 ml-auto">
                    <!-- Performance Widget -->
                    <div v-if="performance !== null" class="hidden sm:flex bg-white border-2 border-[#2CA01C]/20 rounded-2xl px-5 py-2 flex items-center gap-4 shadow-sm group hover:shadow-md transition-all">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-[#2CA01C] uppercase tracking-widest leading-tight">{{ performanceLabel }}</span>
                            <span class="text-xl font-black text-gray-900 leading-none">{{ performance }}%</span>
                        </div>
                        <div class="w-12 bg-gray-100 rounded-full h-2 overflow-hidden shadow-inner border border-gray-50">
                            <div class="bg-gradient-to-r from-[#2CA01C] to-[#238016] h-full rounded-full transition-all duration-700" :style="{ width: performance + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="exportTasks" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-xl font-bold border-2 border-gray-100 shadow-sm transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export
                        </button>

                        <button v-if="$page.props.auth.user.permissions.includes('manage tasks') || $page.props.auth.user.roles.includes('Super Admin')" @click="showCreateModal = true" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            Assign New
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 px-1">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Advanced Filtering Bar -->
                <div class="bg-white border-2 border-gray-100 rounded-3xl p-6 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Search Keywords</label>
                            <div class="relative">
                                <input v-model="filter.search" type="text" placeholder="Title, description..." class="w-full pl-10 pr-3 py-2.5 bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold placeholder-gray-300 transition-all shadow-inner" />
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Time Period</label>
                            <select v-model="filter.filter_type" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold transition-all shadow-inner">
                                <option value="all">All Time</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>

                        <div v-if="filter.filter_type === 'custom'" class="lg:col-span-2 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">From</label>
                                <input v-model="filter.start_date" type="date" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">To</label>
                                <input v-model="filter.end_date" type="date" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Priority & Info</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Project Name</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Owner</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Workflow State</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Chat</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-50">
                                <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-gray-50 transition-all group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-start gap-4">
                                            <div class="mt-1 h-3 w-3 rounded-full flex-shrink-0" :class="task.status === 'completed' ? 'bg-[#2CA01C]' : (task.status === 'in_progress' ? 'bg-blue-400' : 'bg-amber-400')"></div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-black text-gray-900 group-hover:text-[#2CA01C] transition-colors flex items-center gap-2">
                                                    {{ task.title }}
                                                    <span class="text-[9px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-black">#{{ task.id }}</span>
                                                </div>
                                                <div class="text-[11px] text-gray-400 mt-1 font-medium italic line-clamp-1" v-if="task.description">{{ task.description }}</div>
                                                <div class="text-[10px] text-rose-500 font-black mt-2 flex items-center gap-1 uppercase tracking-tighter" v-if="task.due_date">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Deadline: {{ task.due_date }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="inline-flex items-center px-3 py-1 text-[10px] font-black bg-gray-50 text-gray-700 border border-gray-100 rounded-xl uppercase shadow-sm">
                                            {{ task.project ? task.project.name : 'Unlinked' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <img class="h-8 w-8 rounded-full border-2 border-white shadow-sm ring-1 ring-gray-100" :src="'https://ui-avatars.com/api/?name='+(task.assignee?.name || 'U')+'&background=random&color=fff'" alt="Avatar">
                                                <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-white rounded-full border-2 border-white">
                                                    <div class="w-full h-full rounded-full" :class="task.status === 'completed' ? 'bg-[#2CA01C]' : 'bg-amber-400'"></div>
                                                </div>
                                            </div>
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-black text-gray-900 uppercase tracking-tight">{{ task.assignee ? task.assignee.name : 'Idle' }}</span>
                                                    <button v-if="$page.props.auth.user.permissions.includes('manage tasks') || $page.props.auth.user.roles.includes('Super Admin')" 
                                                            @click="openReassignModal(task)" 
                                                            class="text-[9px] text-[#2CA01C] font-black hover:underline uppercase tracking-tighter">
                                                        (Reassign)
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <select v-model="task.status" @change="updateStatus(task.id, $event.target.value)" class="text-[10px] font-black uppercase tracking-widest border-2 rounded-xl px-4 py-2 transition-all cursor-pointer shadow-sm disabled:opacity-50" :class="{
                                            'bg-amber-50 text-amber-700 border-amber-100 hover:bg-amber-100': task.status === 'pending',
                                            'bg-blue-50 text-blue-700 border-blue-100 hover:bg-blue-100': task.status === 'in_progress',
                                            'bg-green-50 text-green-700 border-green-100 hover:bg-green-100': task.status === 'completed'
                                        }">
                                            <option value="pending">⏳ Pending</option>
                                            <option value="in_progress">🔄 In Progress</option>
                                            <option value="completed">✅ Completed</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <button @click="openCommunication(task)" class="relative p-2 rounded-xl bg-gray-50 text-gray-400 hover:bg-[#2CA01C]/10 hover:text-[#2CA01C] transition-all border border-transparent hover:border-[#2CA01C]/20">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                            <span v-if="task.comments?.length > 0" class="absolute -top-1 -right-1 flex h-4 w-4">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#2CA01C] opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-4 w-4 bg-[#2CA01C] text-[8px] font-black text-white items-center justify-center">{{ task.comments.length }}</span>
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="tasks.data.length === 0">
                                    <td colspan="5" class="px-6 py-24 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="bg-gray-50 p-6 rounded-full border border-gray-100 mb-4 shadow-inner">
                                                <svg class="h-12 w-12 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                            </div>
                                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">No matching tasks found for this period</h3>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="tasks.links" class="mt-6" />
            </div>
        </div>

        <!-- Communication Desk (Drawer) -->
        <div v-if="showCommunicationDesk" class="fixed inset-0 z-[60] overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-gray-900 bg-opacity-70 transition-opacity backdrop-blur-sm" @click="showCommunicationDesk = false"></div>
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-md transform transition duration-500 sm:duration-700 translate-x-0">
                        <div class="flex h-full flex-col overflow-y-hidden bg-white shadow-2xl rounded-l-[3rem]">
                            <!-- Header -->
                            <div class="bg-gray-50 px-8 py-8 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tighter" id="slide-over-title">Communication Desk</h2>
                                    <button @click="showCommunicationDesk = false" class="text-gray-400 hover:text-gray-900">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div class="mt-4 flex items-center gap-3">
                                    <div class="h-10 w-10 bg-[#2CA01C]/10 rounded-2xl flex items-center justify-center text-[#2CA01C] font-black">#{{ selectedTask.id }}</div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900 leading-tight uppercase tracking-tight">{{ selectedTask.title }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase">{{ selectedTask.project?.name || 'No Project' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments List -->
                            <div ref="commentScrollArea" class="flex-1 overflow-y-auto px-8 py-6 space-y-6 custom-scrollbar bg-gray-50/30 shadow-inner">
                                <div v-for="comment in selectedTask.comments" :key="comment.id" :class="comment.user_id === $page.props.auth.user.id ? 'flex flex-col items-end' : 'flex flex-col items-start'">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span v-if="comment.user_id !== $page.props.auth.user.id" class="text-[9px] font-black text-[#2CA01C] uppercase tracking-widest">{{ comment.user.name }}</span>
                                        <span class="text-[8px] text-gray-400 font-bold uppercase">{{ new Date(comment.created_at).toLocaleString() }}</span>
                                    </div>
                                    <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm border" :class="comment.user_id === $page.props.auth.user.id ? 'bg-[#2CA01C] text-white border-[#2CA01C]' : 'bg-white text-gray-800 border-gray-100'">
                                        <p class="text-xs font-bold leading-relaxed">{{ comment.comment }}</p>
                                        <div v-if="comment.attachment" class="mt-2 rounded-xl overflow-hidden border border-white/20">
                                            <a :href="'/storage/' + comment.attachment" target="_blank">
                                                <img :src="'/storage/' + comment.attachment" class="w-full h-auto" alt="Screenshot" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="selectedTask.comments?.length === 0" class="flex flex-col items-center justify-center h-full opacity-30 text-center">
                                    <svg class="h-16 w-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                                    <p class="text-xs font-black uppercase tracking-widest">No communication yet</p>
                                </div>
                            </div>

                            <!-- Input Area -->
                            <div class="p-8 bg-white border-t border-gray-100">
                                <form @submit.prevent="postComment" class="space-y-4">
                                    <textarea v-model="commentForm.comment" rows="3" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-xs font-bold shadow-inner placeholder-gray-300 transition-all" placeholder="Explain the issue or update team lead..." required></textarea>
                                    
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <label class="relative flex items-center justify-center px-4 py-2 bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:bg-gray-100 transition-all group">
                                                <svg class="w-4 h-4 text-gray-400 group-hover:text-[#2CA01C]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <span class="ml-2 text-[10px] font-black uppercase text-gray-400 group-hover:text-[#2CA01C]">{{ commentForm.attachment ? 'File Added' : 'Add Screenshot' }}</span>
                                                <input type="file" @change="handleFile" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" />
                                            </label>
                                        </div>
                                        <button type="submit" :disabled="commentForm.processing" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-6 py-2.5 rounded-xl font-black uppercase text-xs shadow-md active:scale-95 transition-all">
                                            Send
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Task Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-70 transition-opacity backdrop-blur-md" @click="showCreateModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[2rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full">
                    <form @submit.prevent="createTask">
                        <div class="bg-white px-8 pt-8 pb-4">
                            <h3 class="text-3xl font-black text-gray-900 mb-8 uppercase tracking-tighter flex items-center gap-4">
                                <span class="bg-[#2CA01C]/10 p-2.5 rounded-2xl">
                                    <svg class="h-8 w-8 text-[#2CA01C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </span>
                                Assign Task
                            </h3>
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Project Link</label>
                                        <select v-model="form.project_id" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" required>
                                            <option value="" disabled>Select Project</option>
                                            <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Responsible Staff</label>
                                        <select v-model="form.assigned_to" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" required>
                                            <option value="" disabled>Select Staff</option>
                                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Task Title</label>
                                    <input v-model="form.title" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" required placeholder="e.g. Design homepage layout" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Detailed Description</label>
                                    <textarea v-model="form.description" rows="4" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" placeholder="Detailed instructions..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Final Soft Deadline</label>
                                    <input v-model="form.due_date" type="date" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" />
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-8 py-6 flex flex-row-reverse gap-4">
                            <button type="submit" :disabled="form.processing" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-10 py-3 rounded-2xl font-black uppercase text-sm shadow-lg hover:shadow-xl transition-all disabled:opacity-50">
                                Create Task
                            </button>
                            <button type="button" @click="showCreateModal = false" class="bg-white border-2 border-gray-200 text-gray-700 px-10 py-3 rounded-2xl font-black uppercase text-sm hover:bg-gray-50 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reassign Task Modal -->
        <div v-if="showReassignModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-70 transition-opacity backdrop-blur-md" @click="showReassignModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[2rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                    <form @submit.prevent="submitReassign">
                        <div class="bg-white px-8 pt-8 pb-4">
                            <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase tracking-tighter flex items-center gap-4">
                                <span class="bg-indigo-50 p-2.5 rounded-2xl">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </span>
                                Reassign Task
                            </h3>
                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Original Title</p>
                                    <p class="text-sm font-bold text-gray-800">{{ selectedTask.title }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select New Owner</label>
                                    <select v-model="reassignForm.assigned_to" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold shadow-inner" required>
                                        <option value="" disabled>Select Staff Member</option>
                                        <option v-for="u in users" :key="u.id" :value="u.id" :disabled="u.id === selectedTask.assigned_to">
                                            {{ u.name }} {{ u.id === selectedTask.assigned_to ? '(Current)' : '' }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-8 py-6 flex flex-row-reverse gap-4">
                            <button type="submit" :disabled="reassignForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-black uppercase text-xs shadow-lg transition-all disabled:opacity-50">
                                Update Assignment
                            </button>
                            <button type="button" @click="showReassignModal = false" class="text-gray-500 px-6 py-2.5 rounded-xl font-black uppercase text-xs hover:bg-gray-100 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
</style>
