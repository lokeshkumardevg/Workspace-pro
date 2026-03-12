<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    project: Object,
    stats: Object
});

const activeTab = ref('business'); // business, proposal, report

const form = useForm({
    name: props.project.name,
    client_name: props.project.client_name || '',
    budget: props.project.budget,
    technology_stack: props.project.technology_stack || '',
    estimated_hours: props.project.estimated_hours,
    team_size: props.project.team_size,
    description: props.project.description || '',
    proposal_content: props.project.proposal_content || '',
    status: props.project.status,
    start_date: props.project.start_date,
    end_date: props.project.end_date,
});

const submit = () => {
    form.put(route('projects.update', props.project.id));
};

const printPage = () => {
    window.print();
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString();
};

const isSuperAdmin = computed(() => usePage().props.auth.user.roles.includes('Super Admin'));

</script>

<template>
    <Head :title="'Project Logic - ' + project.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-600 rounded-2xl shadow-lg ring-4 ring-indigo-50">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">{{ project.name }}</h2>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ project.client_name || 'Direct Business' }} • {{ stats.health }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 no-print">
                    <button @click="printPage" class="bg-gray-900 text-white px-6 py-2.5 rounded-2xl font-black uppercase text-xs shadow-md active:scale-95 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Download PDF / Print
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Navigation Tabs -->
                <div class="flex items-center gap-2 mb-10 no-print">
                    <button @click="activeTab = 'business'" :class="activeTab === 'business' ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-gray-500 hover:bg-gray-50'" 
                            class="px-6 py-3 rounded-2xl font-black uppercase text-xs transition-all border border-transparent shadow-sm">
                        💼 Business Profile
                    </button>
                    <button @click="activeTab = 'proposal'" :class="activeTab === 'proposal' ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-gray-500 hover:bg-gray-50'" 
                            class="px-6 py-3 rounded-2xl font-black uppercase text-xs transition-all border border-transparent shadow-sm">
                        📄 Client Proposal
                    </button>
                    <button @click="activeTab = 'report'" :class="activeTab === 'report' ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-gray-500 hover:bg-gray-50'" 
                            class="px-6 py-3 rounded-2xl font-black uppercase text-xs transition-all border border-transparent shadow-sm">
                        📊 Handover Report
                    </button>
                </div>

                <!-- Business Profile Tab -->
                <div v-if="activeTab === 'business'" class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
                    <form @submit.prevent="submit">
                        <div class="p-10">
                            <h3 class="text-xl font-black text-gray-900 mb-8 uppercase tracking-tight flex items-center gap-3">
                                <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
                                Commercial & Strategic Setup
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div class="md:col-span-2 space-y-6">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Client / Company Name</label>
                                            <input v-model="form.client_name" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-sm font-bold shadow-inner" placeholder="Enter client name" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Total Budget (USD/INR)</label>
                                            <input v-model="form.budget" type="number" step="0.01" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-sm font-bold shadow-inner font-mono" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Technology Stack</label>
                                        <textarea v-model="form.technology_stack" rows="3" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-sm font-bold shadow-inner" placeholder="e.g. Laravel, Vue.js, MySQL, AWS"></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Estimated Hours</label>
                                            <input v-model="form.estimated_hours" type="number" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-sm font-bold shadow-inner" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Core Team Size</label>
                                            <input v-model="form.team_size" type="number" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-sm font-bold shadow-inner" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-6 bg-gray-50 p-8 rounded-[2rem] border border-gray-100">
                                    <h4 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-4">Project Health Index</h4>
                                    <div class="text-center py-6">
                                        <div class="inline-flex items-center justify-center w-32 h-32 rounded-full border-8 border-white shadow-xl bg-white relative overflow-hidden">
                                            <div class="absolute inset-0 bg-indigo-600/10" :style="{ height: stats.progress + '%' }"></div>
                                            <span class="text-3xl font-black text-indigo-700 relative z-10">{{ stats.progress }}%</span>
                                        </div>
                                        <p class="mt-4 text-xs font-black uppercase text-gray-600">{{ stats.health }}</p>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-wide text-gray-400">
                                            <span>Completed Tasks</span>
                                            <span class="text-gray-900">{{ stats.completed_tasks }} / {{ stats.total_tasks }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-indigo-600 h-full transition-all" :style="{ width: stats.progress + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-10 flex justify-end gap-4 no-print">
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-12 py-3 rounded-2xl font-black uppercase text-sm shadow-xl active:scale-95 transition-all">
                                {{ form.processing ? 'Saving...' : '🛡️ Save Business Profile' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Proposal Tab (Client View) -->
                <div v-if="activeTab === 'proposal'" class="bg-white rounded-[2.5rem] shadow-2xl p-16 print:p-0 print:shadow-none border border-gray-100">
                    <div class="flex justify-between items-start mb-16 border-b border-gray-100 pb-16">
                        <div>
                            <h1 class="text-5xl font-black text-gray-900 uppercase tracking-tighter mb-4">Project Proposal</h1>
                            <p class="text-indigo-600 font-black uppercase tracking-widest text-sm">Prepared for: {{ project.client_name || 'Valued Client' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Proposal Date</p>
                            <p class="text-lg font-black text-gray-900">{{ formatDate(new Date()) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-16 mb-16">
                        <div class="md:col-span-2 space-y-10">
                            <section>
                                <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight mb-4 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Project Overview
                                </h3>
                                <p class="text-gray-600 leading-relaxed font-medium">{{ project.description || 'No description provided.' }}</p>
                            </section>

                            <section>
                                <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight mb-4 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.691.34a2 2 0 01-1.783 0l-.691-.34a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547l-.547 1.022a2 2 0 00.547 2.428l.974.974a2 2 0 002.428.547l1.022-.547a2 2 0 00.547-1.022V12"></path></svg>
                                    Technical Architecture
                                </h3>
                                <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100">
                                    <p class="text-indigo-800 font-bold whitespace-pre-wrap">{{ project.technology_stack || 'Technology stack not defined yet.' }}</p>
                                </div>
                            </section>
                        </div>

                        <div class="space-y-8">
                            <div class="bg-gray-900 text-white p-8 rounded-[2rem] shadow-xl">
                                <h4 class="text-[10px] font-black uppercase tracking-widest mb-6 opacity-60">Investment Summary</h4>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-end border-b border-white/10 pb-4">
                                        <span class="text-[10px] font-black uppercase">Fixed Budget</span>
                                        <span class="text-xl font-black tracking-tighter">${{ project.budget?.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex justify-between items-end border-b border-white/10 pb-4">
                                        <span class="text-[10px] font-black uppercase">Est. Timeline</span>
                                        <span class="text-sm font-black">{{ project.estimated_hours }} Hours</span>
                                    </div>
                                    <div class="flex justify-between items-end">
                                        <span class="text-[10px] font-black uppercase">Supervision</span>
                                        <span class="text-sm font-black">{{ project.team_size }} Members</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-8 border-2 border-dashed border-gray-200 rounded-[2rem]">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Milestones</h4>
                                <ul class="space-y-4">
                                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span> Phase 1: Planning
                                    </li>
                                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span> Phase 2: Design
                                    </li>
                                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span> Phase 3: Launch
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Handover Report Tab (Internal/Documentation) -->
                <div v-if="activeTab === 'report'" class="space-y-8">
                    <div class="bg-indigo-900 text-white p-12 rounded-[3rem] shadow-2xl relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-8">
                        <div class="relative z-10 text-center md:text-left">
                            <h2 class="text-4xl font-black uppercase tracking-tighter mb-2">Project Handover Doc</h2>
                            <p class="text-indigo-200 font-bold uppercase tracking-widest text-[10px]">Auto-Generated Intelligence Report • Internal Use Only</p>
                        </div>
                        <div class="flex gap-4 relative z-10">
                            <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 text-center w-32 shadow-inner">
                                <span class="block text-2xl font-black">{{ stats.completed_tasks }}</span>
                                <span class="text-[9px] font-black uppercase opacity-60">Finished</span>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 text-center w-32 shadow-inner">
                                <span class="block text-2xl font-black">{{ stats.total_tasks - stats.completed_tasks }}</span>
                                <span class="text-[9px] font-black uppercase opacity-60">Pending</span>
                            </div>
                        </div>
                        <!-- Abstract design element -->
                        <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Documentation Section -->
                        <div class="bg-white p-10 rounded-[2.5rem] border border-gray-100 shadow-lg">
                            <h3 class="text-lg font-black text-gray-900 mb-6 uppercase tracking-tight flex items-center gap-3">
                                🔧 Architecture & Process
                            </h3>
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Tech Stack Overview</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="tech in (project.technology_stack || 'N/A').split(',')" :key="tech" class="px-4 py-1.5 bg-gray-50 text-gray-700 text-[10px] font-black uppercase rounded-lg border border-gray-100">
                                            {{ tech.trim() }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Current Roadmap</h4>
                                    <div class="space-y-4">
                                        <div v-for="task in project.tasks.slice(0, 5)" :key="task.id" class="flex items-center gap-4 p-4 rounded-2xl bg-gray-50/50 border border-gray-50">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-[10px] font-black" 
                                                 :class="task.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                                                {{ task.status === 'completed' ? '✓' : '...' }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-gray-900 uppercase tracking-tight">{{ task.title }}</p>
                                                <p class="text-[9px] text-gray-400 font-bold">Assigned to: {{ task.assignee?.name || 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Knowledge Base / Comments Section -->
                        <div class="bg-white p-10 rounded-[2.5rem] border border-gray-100 shadow-lg">
                            <h3 class="text-lg font-black text-gray-900 mb-6 uppercase tracking-tight flex items-center gap-3">
                                🧠 Wisdom & Coordination Feed
                            </h3>
                            <div class="space-y-6">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Recent communication for handover clarity:</p>
                                <div class="space-y-6 h-[400px] overflow-y-auto pr-4 scrollbar-thin">
                                    <template v-for="task in project.tasks" :key="task.id">
                                        <div v-for="comment in task.comments" :key="comment.id" class="relative pl-6 border-l-2 border-gray-100 pb-6">
                                            <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-4 border-indigo-600 shadow-sm"></div>
                                            <div class="flex flex-col">
                                                <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest mb-1">{{ comment.user.name }} • Task #{{ task.id }}</span>
                                                <p class="text-xs font-medium text-gray-600 bg-gray-50 p-4 rounded-2xl border border-gray-50">{{ comment.comment }}</p>
                                            </div>
                                        </div>
                                    </template>
                                    <div v-if="project.tasks.length === 0" class="text-center py-20">
                                        <p class="text-gray-400 font-black uppercase text-xs">No active logs available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>

    <style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        body { background: white !important; }
        .max-w-7xl { max-width: 100% !important; margin: 0 !important; }
        .p-16 { padding: 0 !important; }
    }
    </style>
</template>
