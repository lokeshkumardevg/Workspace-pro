<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, watch, reactive, nextTick, computed } from 'vue';
import debounce from 'lodash/debounce';
import draggable from 'vuedraggable';

const props = defineProps({
    tasks: Object,
    projects: Array,
    users: Array,
    performance: Number,
    performanceLabel: String,
    filters: Object,
});

const page = usePage();

const isPrivileged = ref(page.props.auth.user.roles.some(r => ['Super Admin', 'Admin', 'super admin', 'admin', 'Superadmin', 'superadmin'].includes(r)));

const filter = reactive({
    search: props.filters?.search || '',
    filter_type: props.filters?.filter_type || 'all',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    employee_id: props.filters?.employee_id || '',
});

const currentView = ref('list'); // 'list' or 'board'
const toggleView = (view) => currentView.value = view;

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
watch(() => filter.employee_id, updateFilters);

const showExportDropdown = ref(false);

const exportTasks = (format = 'excel') => {
    showExportDropdown.value = false;
    const params = new URLSearchParams({ ...filter, format }).toString();
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
    priority: 'medium',
    time_spent: '',
});

const createTask = () => {
    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

// Edit Task Modal
const showEditModal = ref(false);
const editForm = useForm({
    project_id: '',
    assigned_to: '',
    title: '',
    description: '',
    due_date: '',
    priority: 'medium',
});

const openEditModal = (task) => {
    selectedTask.value = task;
    editForm.project_id = task.project_id;
    editForm.assigned_to = task.assigned_to || '';
    editForm.title = task.title;
    editForm.description = task.description || '';
    editForm.due_date = task.due_date ? task.due_date.slice(0, 16) : ''; // Format for datetime-local
    editForm.priority = task.priority;
    editForm.time_spent = task.time_spent || '';
    showEditModal.value = true;
};

const updateTask = () => {
    editForm.put(route('tasks.update', selectedTask.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};

// Quick Add Task
const quickAddForm = useForm({
    title: '',
    project_id: props.projects.length > 0 ? props.projects[0].id : '',
    assigned_to: '',
    priority: 'medium',
    time_spent: '',
});

const quickAddTask = () => {
    if (!quickAddForm.title.trim()) return;
    
    quickAddForm.post(route('tasks.store'), {
        onSuccess: () => {
            quickAddForm.title = '';
        },
        preserveScroll: true,
    });
};

const updateStatus = (taskId, status) => {
    router.put(route('tasks.status', taskId), { 
        status: status,
    }, { preserveScroll: true });
};

const claimTask = (taskId) => {
    router.put(route('tasks.reassign', taskId), { assigned_to: page.props.auth.user.id }, { preserveScroll: true });
};

const onMove = (evt, status) => {
    const task = evt.added?.element || evt.moved?.element;
    if (task) {
        updateStatus(task.id, status);
    }
};

const pendingTasks = computed({
    get: () => props.tasks.data.filter(t => t.status === 'pending'),
    set: (val) => {} // Board view handles updates via updateStatus
});

const inProgressTasks = computed({
    get: () => props.tasks.data.filter(t => t.status === 'in_progress'),
    set: (val) => {}
});

const testingTasks = computed({
    get: () => props.tasks.data.filter(t => t.status === 'testing'),
    set: (val) => {}
});

const completedTasks = computed({
    get: () => props.tasks.data.filter(t => t.status === 'completed'),
    set: (val) => {}
});

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

const deleteTask = (taskId) => {
    if (confirm('Are you sure you want to delete this task?')) {
        router.delete(route('tasks.destroy', taskId));
    }
};

const formatDate = (d) => {
    if (!d) return '—';
    const date = new Date(d);
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (d) => {
    if (!d) return '—';
    const date = new Date(d);
    return date.toLocaleString('en-IN', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};
</script>

<template>
    <Head title="Tasks Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6">
                <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                    Tasks
                </h2>

                <div class="flex items-center gap-4 ml-auto">
                    <!-- Performance Widget -->
                    <div v-if="performance !== null" class="hidden sm:flex bg-white border-2 border-indigo-100 rounded-2xl px-5 py-2 flex items-center gap-4 shadow-sm group hover:shadow-md transition-all">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest leading-tight">{{ performanceLabel || 'Performance' }}</span>
                            <span class="text-xl font-black text-gray-900 leading-none">{{ performance }}%</span>
                        </div>
                        <div class="w-12 bg-gray-100 rounded-full h-2 overflow-hidden shadow-inner border border-gray-50">
                            <div class="bg-gradient-to-r from-[#2CA01C] to-[#238016] h-full rounded-full transition-all duration-700" :style="{ width: performance + '%' }"></div>
                        </div>
                    </div>

                    <!-- View Switcher -->
                    <div class="flex items-center bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-inner">
                        <button @click="toggleView('list')" :class="currentView === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all duration-300">List</button>
                        <button @click="toggleView('board')" :class="currentView === 'board' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all duration-300">Board</button>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Excel Export -->
                        <button @click="exportTasks('excel')" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-4 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[10px] uppercase whitespace-nowrap active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export Excel
                        </button>

                        <!-- Word Export -->
                        <button @click="exportTasks('doc')" class="bg-indigo-600 hover:bg-gray-900 text-white px-4 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[10px] uppercase whitespace-nowrap active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export Word
                        </button>

                        <button v-if="page.props.auth?.user" @click="showCreateModal = true" class="bg-indigo-600 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            Add Task
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
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Search</label>
                            <div class="relative">
                                <input v-model="filter.search" type="text" placeholder="Search tasks..." class="w-full pl-10 pr-3 py-2.5 bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold placeholder-gray-300 transition-all shadow-inner" />
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>
                            </div>
                        </div>

                        <div v-if="isPrivileged">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Employee View</label>
                            <select v-model="filter.employee_id" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold transition-all shadow-inner">
                                <option value="">All Tasks (Company Wide)</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Time Period</label>
                            <select v-model="filter.filter_type" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold transition-all shadow-inner">
                                <option value="all">All Time</option>
                                <option value="daily">Today</option>
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

                <!-- Quick Add Task Bar -->
                <div class="bg-indigo-50 border-2 border-indigo-100 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row gap-4 items-center">
                    <div class="flex-1 w-full">
                        <textarea v-model="quickAddForm.title" 
                                  placeholder="⚡ Bulk Add Tasks: Paste your list here or type multiple lines (One task per line)..." 
                                  rows="2"
                                  class="w-full px-6 py-4 bg-white border-2 border-indigo-100 rounded-[1.2rem] text-sm font-black focus:ring-4 focus:ring-indigo-100 placeholder-gray-400 transition-all shadow-md resize-none min-h-[80px]"
                                  @keydown.enter.exact.prevent="quickAddTask"></textarea>
                    </div>
                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <select v-model="quickAddForm.project_id" 
                                class="flex-1 md:w-40 px-5 py-4 bg-white border-2 border-indigo-100 rounded-[1.2rem] text-[10px] font-black uppercase tracking-tight focus:ring-4 focus:ring-indigo-100 transition-all shadow-md">
                            <option value="" disabled>Project</option>
                            <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
                        </select>
                        <select v-if="isPrivileged" 
                                v-model="quickAddForm.assigned_to" 
                                class="flex-1 md:w-40 px-5 py-4 bg-white border-2 border-indigo-100 rounded-[1.2rem] text-[10px] font-black uppercase tracking-tight focus:ring-4 focus:ring-indigo-100 transition-all shadow-md">
                            <option value="" disabled>Assign To</option>
                            <option value="">Unassigned</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <button 
                            @click="quickAddTask" 
                            :disabled="quickAddForm.processing"
                            class="bg-indigo-600 hover:bg-gray-900 text-white px-10 py-4 rounded-[1.2rem] font-black uppercase text-[10px] shadow-lg transition-all active:scale-95 whitespace-nowrap disabled:opacity-50"
                        >
                            {{ quickAddForm.processing ? 'Adding...' : 'Add All' }}
                        </button>
                    </div>
                </div>

                <div v-if="currentView === 'list'">
                    <!-- Enhanced Operational DataTable -->
                    <DataTable 
                        :headers="[
                            { key: 'status_check', label: 'Tick', width: '50px' },
                            { key: 'title', label: 'Task Name', sortable: true },
                            { key: 'project', label: 'Project' },
                            { key: 'assignee', label: 'Assigned To' },
                            { key: 'time_spent', label: 'Time Spent' },
                            { key: 'status', label: 'Status' },
                            { key: 'actions', label: 'Comments' }
                        ]"
                        :items="tasks.data"
                        placeholder="Search tasks..."
                        @search="val => filter.search = val"
                    >
                        <template #row="{ item: task }">
                            <td class="px-6 py-6 w-[50px]">
                                <input type="checkbox" 
                                       :checked="task.status === 'completed'" 
                                       @change="updateStatus(task.id, task.status === 'completed' ? 'pending' : 'completed')" 
                                       class="w-5 h-5 rounded-lg border-2 border-gray-100 text-emerald-500 focus:ring-emerald-500 cursor-pointer transition-all active:scale-95 shadow-inner" />
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1.5 h-3 w-3 rounded-full flex-shrink-0 border-2 border-white shadow-sm ring-1 ring-gray-100" :class="task.status === 'completed' ? 'bg-emerald-500' : (task.status === 'in_progress' ? 'bg-indigo-500' : 'bg-amber-400')"></div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <button @click="openCommunication(task)" class="text-sm font-black text-gray-900 hover:text-indigo-600 transition-colors flex items-center gap-2 uppercase tracking-tight text-left">
                                                {{ task.title }}
                                                <span class="text-[9px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded-lg border border-gray-200">ID:{{ task.id }}</span>
                                            </button>
                                            <!-- Edit Action (For creator or admin) -->
                                            <button v-if="isPrivileged || task.created_by === $page.props.auth.user.id" 
                                                    @click="openEditModal(task)" 
                                                    class="p-1 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 transition-all" title="Edit Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button v-if="isPrivileged || task.created_by === $page.props.auth.user.id"
                                                    @click="deleteTask(task.id)"
                                                    class="p-1 hover:bg-rose-50 rounded-lg text-gray-400 hover:text-rose-600 transition-all ml-1" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1.5">
                                            <span v-if="task.priority" class="text-[8px] font-black uppercase px-2 py-0.5 rounded-md border shadow-sm"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 border-emerald-100': task.priority === 'low',
                                                    'bg-amber-50 text-amber-700 border-amber-100': task.priority === 'medium',
                                                    'bg-orange-50 text-orange-700 border-orange-100': task.priority === 'high',
                                                    'bg-rose-50 text-rose-700 border-rose-100 animate-pulse': task.priority === 'urgent'
                                                }">
                                                {{ task.priority }} Priority
                                            </span>
                                            <div v-if="task.time_spent" class="text-[9px] bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md border border-indigo-100 font-black flex items-center gap-1 uppercase tracking-widest pl-2">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Time Taken: {{ task.time_spent }}
                                            </div>
                                            <div v-if="task.due_date" class="text-[9px] text-rose-500 font-black flex items-center gap-1 uppercase tracking-widest pl-2 border-l border-gray-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Deadline: {{ formatDateTime(task.due_date) }}
                                            </div>
                                            <div class="text-[9px] text-gray-500 font-black flex items-center gap-1 uppercase tracking-widest pl-2 border-l border-gray-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                Created: {{ formatDate(task.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1.5 bg-gray-950 text-white text-[9px] font-black uppercase tracking-[0.2em] rounded-xl shadow-lg shadow-indigo-100/50">
                                    {{ task.project ? task.project.name : 'Independent' }}
                                </span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="relative group/avatar">
                                        <img class="h-9 w-9 rounded-[1.2rem] border-2 border-white shadow-md ring-1 ring-gray-100 grayscale hover:grayscale-0 transition-all" :src="'https://ui-avatars.com/api/?name='+(task.assignee?.name || 'U')+'&background=random&color=fff'" alt="Avatar">
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-white rounded-full flex items-center justify-center shadow-sm">
                                            <div class="w-2 h-2 rounded-full" :class="task.status === 'completed' ? 'bg-emerald-500' : 'bg-amber-400'"></div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-gray-900 uppercase tracking-tight">{{ task.assignee ? task.assignee.name : 'Unassigned' }}</span>
                                            <button v-if="isPrivileged" 
                                                    @click="openReassignModal(task)" 
                                                    class="p-1 bg-gray-50 text-indigo-600 rounded-lg border border-gray-100 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <select v-model="task.status" @change="updateStatus(task.id, $event.target.value)" class="text-[9px] font-black uppercase tracking-[0.2em] border-2 rounded-2xl px-5 py-2.5 transition-all cursor-pointer shadow-xl border-transparent focus:ring-4 focus:ring-indigo-50" :class="{
                                    'bg-amber-50 text-amber-700': task.status === 'pending',
                                    'bg-indigo-50 text-indigo-700': task.status === 'in_progress',
                                    'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-200': task.status === 'testing',
                                    'bg-emerald-50 text-emerald-700': task.status === 'completed'
                                }">
                                    <option value="pending">⏳ Pending</option>
                                    <option value="in_progress">🔄 In Progress</option>
                                    <option value="testing">🧪 Testing</option>
                                    <option value="completed">✅ Completed</option>
                                </select>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="openCommunication(task)" class="relative p-3 rounded-[1.2rem] bg-indigo-50 text-indigo-600 hover:bg-gray-900 hover:text-white transition-all border border-indigo-100 group shadow-lg shadow-indigo-50 active:scale-90">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                        <span v-if="task.comments?.length > 0" class="absolute -top-1 -right-1 flex h-5 w-5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-500 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-5 w-5 bg-rose-600 text-[9px] font-black text-white items-center justify-center border-2 border-white">{{ task.comments.length }}</span>
                                        </span>
                                    </button>
                                    <button @click="openCommunication(task)" class="bg-gray-50 hover:bg-gray-100 text-[10px] font-black uppercase text-gray-400 px-4 py-2.5 rounded-xl border border-gray-100 active:scale-95 transition-all">Details</button>
                                </div>
                            </td>
                        </template>
                    </DataTable>

                    <div class="flex justify-end pr-4 mt-6">
                        <Pagination :links="tasks.links" />
                    </div>
                </div>

                <!-- Jira Board View (Draggable) -->
                <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-12 overflow-x-auto min-w-[1250px] md:min-w-0">
                    <!-- Column: Pending (To Do) -->
                    <div class="flex flex-col bg-gray-100/40 rounded-[2.5rem] p-6 border-2 border-white shadow-inner min-h-[650px]">
                        <div class="flex items-center justify-between mb-8 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-gray-600">Pending</h3>
                            </div>
                            <span class="bg-gray-200 text-gray-500 text-[10px] px-3 py-1 rounded-full font-black">{{ pendingTasks.length }}</span>
                        </div>
                        
                        <draggable 
                            v-model="pendingTasks" 
                            group="tasks" 
                            item-key="id" 
                            class="flex-1 space-y-4"
                            @change="evt => onMove(evt, 'pending')"
                        >
                            <template #item="{ element: task }">
                                <div class="bg-white p-5 rounded-[1.8rem] shadow-sm border-2 border-transparent hover:border-indigo-400 hover:shadow-xl hover:-translate-y-1 transition-all cursor-grab active:cursor-grabbing group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" 
                                                   :checked="task.status === 'completed'" 
                                                   @change="updateStatus(task.id, task.status === 'completed' ? 'pending' : 'completed')" 
                                                   class="w-4 h-4 rounded-md border-gray-100 text-emerald-500 focus:ring-emerald-500 cursor-pointer shadow-inner" />
                                            <div class="flex flex-col gap-1">
                                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter flex items-center gap-1">ID: {{ task.id }} <span class="mx-0.5">•</span> {{ formatDate(task.created_at) }}</span>
                                                <span class="text-[8px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase tracking-tight">{{ task.project?.name || 'Independent' }}</span>
                                            </div>
                                        </div>
                                        <div v-if="task.priority" class="flex flex-col items-end gap-1">
                                            <span class="h-2 w-2 rounded-full" :class="{ 'bg-emerald-400': task.priority === 'low', 'bg-amber-400': task.priority === 'medium', 'bg-orange-500': task.priority === 'high', 'bg-rose-500': task.priority === 'urgent' }"></span>
                                        </div>
                                    </div>
                                    <h4 class="text-xs font-black text-gray-900 leading-snug uppercase tracking-tight mb-4 group-hover:text-indigo-600 transition-colors">{{ task.title }}</h4>
                                    
                                    <div class="flex items-center justify-between mt-6">
                                        <div class="flex items-center">
                                            <div v-if="!task.assigned_to" class="flex flex-col gap-2">
                                                <button @click.stop="claimTask(task.id)" class="bg-emerald-500 hover:bg-emerald-600 text-white text-[8px] font-black uppercase px-3 py-1.5 rounded-xl shadow-lg shadow-emerald-100 flex items-center gap-1 transition-all active:scale-95">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                                    Claim Task
                                                </button>
                                                <span class="text-[8px] font-black text-rose-400 flex items-center gap-1">⚠️ Unassigned</span>
                                            </div>
                                            <div v-else class="flex items-center gap-2">
                                                <img class="h-8 w-8 rounded-xl border-2 border-white shadow-sm" :src="'https://ui-avatars.com/api/?name='+(task.assignee?.name || 'U')+'&background=6366f1&color=fff'" alt="Avatar">
                                                <span class="text-[9px] font-black text-gray-500 uppercase tracking-tight">{{ task.assignee.name }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button v-if="isPrivileged || task.created_by === $page.props.auth.user.id" 
                                                    @click.stop="deleteTask(task.id)" 
                                                    class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                            <button @click.stop="openCommunication(task)" class="p-2 bg-gray-50 text-gray-400 rounded-xl hover:bg-gray-900 hover:text-white transition-all shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Column: In Progress -->
                    <div class="flex flex-col bg-gray-100/40 rounded-[2.5rem] p-6 border-2 border-white shadow-inner min-h-[650px]">
                        <div class="flex items-center justify-between mb-8 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-indigo-600">In Progress</h3>
                            </div>
                            <span class="bg-indigo-100 text-indigo-600 text-[10px] px-3 py-1 rounded-full font-black">{{ inProgressTasks.length }}</span>
                        </div>

                        <draggable 
                            v-model="inProgressTasks" 
                            group="tasks" 
                            item-key="id" 
                            class="flex-1 space-y-4"
                            @change="evt => onMove(evt, 'in_progress')"
                        >
                            <template #item="{ element: task }">
                                <div class="bg-white p-5 rounded-[1.8rem] shadow-sm border-2 border-transparent hover:border-indigo-400 hover:shadow-xl hover:-translate-y-1 transition-all cursor-grab active:cursor-grabbing group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter flex items-center gap-1">ID: {{ task.id }} <span class="mx-0.5">•</span> {{ formatDate(task.created_at) }}</span>
                                            <span class="text-[8px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase tracking-tight">{{ task.project?.name || 'Independent' }}</span>
                                        </div>
                                        <div v-if="task.priority" class="h-2 w-2 rounded-full" :class="{ 'bg-emerald-400': task.priority === 'low', 'bg-amber-400': task.priority === 'medium', 'bg-orange-500': task.priority === 'high', 'bg-rose-500': task.priority === 'urgent' }"></div>
                                    </div>
                                    <h4 class="text-xs font-black text-gray-900 leading-snug uppercase tracking-tight mb-2 group-hover:text-indigo-600 transition-colors">{{ task.title }}</h4>
                                    
                                    <div v-if="task.time_spent" class="inline-flex items-center gap-1 text-[8px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg border border-indigo-100 uppercase tracking-widest mb-4">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ task.time_spent }}
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-6">
                                        <div class="flex items-center gap-2">
                                            <img class="h-8 w-8 rounded-xl border-2 border-white shadow-sm" :src="'https://ui-avatars.com/api/?name='+(task.assignee?.name || 'U')+'&background=6366f1&color=fff'" alt="Avatar">
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-tight">{{ task.assignee?.name || 'Self' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button v-if="isPrivileged || task.created_by === $page.props.auth.user.id" 
                                                    @click.stop="deleteTask(task.id)" 
                                                    class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                            <button @click.stop="openCommunication(task)" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-gray-900 hover:text-white transition-all shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Column: Testing -->
                    <div class="flex flex-col bg-gray-100/40 rounded-[2.5rem] p-6 border-2 border-white shadow-inner min-h-[650px]">
                        <div class="flex items-center justify-between mb-8 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-fuchsia-500 animate-pulse"></div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-fuchsia-600">Testing</h3>
                            </div>
                            <span class="bg-fuchsia-100 text-fuchsia-600 text-[10px] px-3 py-1 rounded-full font-black">{{ testingTasks.length }}</span>
                        </div>

                        <draggable 
                            v-model="testingTasks" 
                            group="tasks" 
                            item-key="id" 
                            class="flex-1 space-y-4"
                            @change="evt => onMove(evt, 'testing')"
                        >
                            <template #item="{ element: task }">
                                <div class="bg-white p-5 rounded-[1.8rem] shadow-sm border-2 border-transparent hover:border-fuchsia-400 hover:shadow-xl hover:-translate-y-1 transition-all cursor-grab active:cursor-grabbing group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter flex items-center gap-1">ID: {{ task.id }} <span class="mx-0.5">•</span> {{ formatDate(task.created_at) }}</span>
                                            <span class="text-[8px] font-black text-fuchsia-500 bg-fuchsia-50 px-2 py-0.5 rounded-lg border border-fuchsia-100 uppercase tracking-tight">{{ task.project?.name || 'Independent' }}</span>
                                        </div>
                                        <div v-if="task.priority" class="h-2 w-2 rounded-full" :class="{ 'bg-emerald-400': task.priority === 'low', 'bg-amber-400': task.priority === 'medium', 'bg-orange-500': task.priority === 'high', 'bg-rose-500': task.priority === 'urgent' }"></div>
                                    </div>
                                    <h4 class="text-xs font-black text-gray-900 leading-snug uppercase tracking-tight mb-2 group-hover:text-fuchsia-600 transition-colors">{{ task.title }}</h4>
                                    
                                    <div v-if="task.time_spent" class="inline-flex items-center gap-1 text-[8px] font-black text-fuchsia-600 bg-fuchsia-50 px-2 py-1 rounded-lg border border-fuchsia-100 uppercase tracking-widest mb-4">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ task.time_spent }}
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-6">
                                        <div class="flex items-center gap-2">
                                            <img class="h-8 w-8 rounded-xl border-2 border-white shadow-sm" :src="'https://ui-avatars.com/api/?name='+(task.assignee?.name || 'U')+'&background=d946ef&color=fff'" alt="Avatar">
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-tight">{{ task.assignee?.name || 'Self' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button v-if="isPrivileged || task.created_by === $page.props.auth.user.id" 
                                                    @click.stop="deleteTask(task.id)" 
                                                    class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                            <button @click.stop="openCommunication(task)" class="p-2 bg-fuchsia-50 text-fuchsia-600 rounded-xl hover:bg-gray-900 hover:text-white transition-all shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Column: Done -->
                    <div class="flex flex-col bg-gray-100/40 rounded-[2.5rem] p-6 border-2 border-white shadow-inner min-h-[650px]">
                        <div class="flex items-center justify-between mb-8 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-emerald-600">Completed</h3>
                            </div>
                            <span class="bg-emerald-100 text-emerald-600 text-[10px] px-3 py-1 rounded-full font-black">{{ completedTasks.length }}</span>
                        </div>

                        <draggable 
                            v-model="completedTasks" 
                            group="tasks" 
                            item-key="id" 
                            class="flex-1 space-y-4"
                            @change="evt => onMove(evt, 'completed')"
                        >
                            <template #item="{ element: task }">
                                <div class="bg-white/80 p-5 rounded-[1.8rem] shadow-sm border-2 border-transparent hover:border-emerald-400 hover:shadow-xl hover:-translate-y-1 transition-all cursor-grab active:cursor-grabbing group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter flex items-center gap-1">ID: {{ task.id }} <span class="mx-0.5">•</span> {{ formatDate(task.created_at) }}</span>
                                        </div>
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <h4 class="text-xs font-black text-gray-400 line-through uppercase tracking-tight mb-4 group-hover:text-emerald-700 transition-colors">{{ task.title }}</h4>
                                    
                                    <div class="flex items-center justify-between mt-6">
                                        <div class="flex items-center gap-2 grayscale group-hover:grayscale-0 transition-all">
                                            <img class="h-8 w-8 rounded-xl border-2 border-white shadow-sm" :src="'https://ui-avatars.com/api/?name='+(task.assignee?.name || 'U')+'&background=10b981&color=fff'" alt="Avatar">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button v-if="isPrivileged || task.created_by === $page.props.auth.user.id" 
                                                    @click.stop="deleteTask(task.id)" 
                                                    class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                            <span class="text-[8px] font-black text-emerald-500 uppercase tracking-widest bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 shadow-sm opacity-60 group-hover:opacity-100">Verified Done</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
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
                            <div v-if="selectedTask" class="bg-gray-50 px-8 py-8 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tighter" id="slide-over-title">Task Comments</h2>
                                    <button @click="showCommunicationDesk = false" class="text-gray-400 hover:text-gray-900">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div class="mt-4 flex items-center gap-3">
                                    <div class="h-10 w-10 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-black">#{{ selectedTask.id }}</div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900 leading-tight uppercase tracking-tight">{{ selectedTask.title }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase">{{ selectedTask.project?.name || 'General' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments List -->
                            <div v-if="selectedTask" ref="commentScrollArea" class="flex-1 overflow-y-auto px-8 py-6 space-y-6 custom-scrollbar bg-gray-50/30 shadow-inner">
                                <div v-for="comment in selectedTask.comments" :key="comment.id" :class="comment.user_id === $page.props.auth.user.id ? 'flex flex-col items-end' : 'flex flex-col items-start'">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span v-if="comment.user_id !== $page.props.auth.user.id" class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">{{ comment.user.name }}</span>
                                        <span class="text-[8px] text-gray-400 font-bold uppercase">{{ new Date(comment.created_at).toLocaleString() }}</span>
                                    </div>
                                    <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm border" :class="comment.user_id === $page.props.auth.user.id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-800 border-gray-100'">
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
                                    <textarea v-model="commentForm.comment" rows="3" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-xs font-bold shadow-inner placeholder-gray-300 transition-all" placeholder="Write a comment..." required></textarea>
                                    
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <label class="relative flex items-center justify-center px-4 py-2 bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:bg-gray-100 transition-all group">
                                                <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <span class="ml-2 text-[10px] font-black uppercase text-gray-400 group-hover:text-indigo-600">{{ commentForm.attachment ? 'File Added' : 'Add File' }}</span>
                                                <input type="file" @change="handleFile" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" />
                                            </label>
                                        </div>
                                        <button type="submit" :disabled="commentForm.processing" class="bg-indigo-600 hover:bg-gray-900 text-white px-6 py-2.5 rounded-xl font-black uppercase text-xs shadow-md active:scale-95 transition-all">
                                            Post
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
        <Modal :show="showCreateModal" @close="showCreateModal = false" title="Assign New Task" maxWidth="2xl">
            <form @submit.prevent="createTask" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Select Project</label>
                        <select v-model="form.project_id" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                            <option value="" disabled>Select Project</option>
                            <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
                        </select>
                    </div>
                    <div v-if="isPrivileged">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Assign To</label>
                        <select v-model="form.assigned_to" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5">
                            <option value="">Unassigned (Open Pool)</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.id === $page.props.auth.user.id ? 'Myself' : u.name }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Task Title</label>
                        <input v-model="form.title" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required placeholder="Enter task title..." />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Description</label>
                        <textarea v-model="form.description" rows="4" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner placeholder-gray-300" placeholder="Enter task details..."></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Due Date & Time</label>
                        <input v-model="form.due_date" type="datetime-local" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Priority</label>
                        <select v-model="form.priority" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                            <option value="low">🟢 Low</option>
                            <option value="medium">🟡 Medium</option>
                            <option value="high">🟠 High</option>
                            <option value="urgent">🔴 Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Time Spent (Optional)</label>
                        <input v-model="form.time_spent" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" placeholder="e.g. 2 hours" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4">
                    <button type="button" @click="showCreateModal = false" :disabled="form.processing" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all active:scale-95 disabled:opacity-50">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : 'Save Task' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Edit Task Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" title="Edit Task" maxWidth="2xl">
            <form @submit.prevent="updateTask" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Select Project</label>
                        <select v-model="editForm.project_id" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                            <option value="" disabled>Select Project</option>
                            <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
                        </select>
                    </div>
                    <div v-if="isPrivileged">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Assign To</label>
                        <select v-model="editForm.assigned_to" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5">
                            <option value="">Unassigned (Open Pool)</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Task Title</label>
                        <input v-model="editForm.title" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Description</label>
                        <textarea v-model="editForm.description" rows="4" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner placeholder-gray-300"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Due Date & Time</label>
                        <input v-model="editForm.due_date" type="datetime-local" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Priority</label>
                        <select v-model="editForm.priority" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                            <option value="low">🟢 Low</option>
                            <option value="medium">🟡 Medium</option>
                            <option value="high">🟠 High</option>
                            <option value="urgent">🔴 Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1.5 ml-1">Time Spent</label>
                        <input v-model="editForm.time_spent" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" placeholder="e.g. 2 hours" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4">
                    <button type="button" @click="showEditModal = false" :disabled="editForm.processing" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all active:scale-95 disabled:opacity-50">Cancel</button>
                    <button type="submit" :disabled="editForm.processing" class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95 disabled:opacity-50">
                        {{ editForm.processing ? 'Updating...' : 'Update Task' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Reassign Task Modal -->
        <Modal :show="showReassignModal" @close="showReassignModal = false" title="Reassign Task" maxWidth="md">
            <form v-if="selectedTask" @submit.prevent="submitReassign" class="space-y-8">
                <div class="p-6 bg-gray-50 rounded-[2rem] border border-gray-100 flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-4 border border-indigo-50">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Task</p>
                    <p class="text-sm font-black text-gray-800 uppercase tracking-tight">{{ selectedTask.title }}</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">New Owner</label>
                    <select v-model="reassignForm.assigned_to" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                        <option value="" disabled>Select Employee</option>
                        <option v-for="u in users" :key="u.id" :value="u.id" :disabled="u.id === selectedTask.assigned_to">
                            {{ u.name }} {{ u.id === selectedTask.assigned_to ? '(Current)' : '' }}
                        </option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" @click="showReassignModal = false" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="reassignForm.processing" class="bg-indigo-600 hover:bg-gray-900 text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg transition-all active:scale-95">Update</button>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
</style>
