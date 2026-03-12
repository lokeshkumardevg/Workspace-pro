<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    project: Object,
    stats: Object,
    tab: String
});

const activeTab = ref(props.tab || 'business'); // business, proposal, report

const form = useForm({
    name: props.project.name,
    client_name: props.project.client_name || '',
    budget: props.project.budget,
    technology_stack: props.project.technology_stack || '',
    estimated_hours: props.project.estimated_hours,
    team_size: props.project.team_size,
    description: props.project.description || '',
    proposal_content: props.project.proposal_content || '',
    handover_notes: props.project.handover_notes || '',
    target_audience: props.project.target_audience || '',
    security_measures: props.project.security_measures || '',
    project_scope: props.project.project_scope || '',
    milestones_content: props.project.milestones_content || '',
    deliverables: props.project.deliverables || '',
    maintenance_support: props.project.maintenance_support || '',
    terms_conditions: props.project.terms_conditions || '',
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
                                    <div class="mt-6">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Handover Instructions (Internal)</label>
                                        <textarea v-model="form.handover_notes" rows="4" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-sm font-bold shadow-inner" placeholder="Tech notes for developers..."></textarea>
                                    </div>

                                    <!-- NEW: Proposal Advanced Sections -->
                                    <div class="mt-12 space-y-8 p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100">
                                        <h3 class="text-sm font-black text-indigo-700 uppercase tracking-widest border-b border-indigo-100 pb-4">📝 Advanced Proposal Content (Page 2-4)</h3>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Target Audience</label>
                                                <textarea v-model="form.target_audience" rows="3" class="w-full bg-white border-gray-100 rounded-xl text-xs font-bold shadow-sm" placeholder="Define end-users..."></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Project Scope</label>
                                                <textarea v-model="form.project_scope" rows="3" class="w-full bg-white border-gray-100 rounded-xl text-xs font-bold shadow-sm" placeholder="Boundaries of work..."></textarea>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">🛡️ Security & Scalability Protocols</label>
                                            <textarea v-model="form.security_measures" rows="3" class="w-full bg-white border-gray-100 rounded-xl text-xs font-bold shadow-sm" placeholder="SSL, OAuth2, Encryption details..."></textarea>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Milestones & Phases</label>
                                                <textarea v-model="form.milestones_content" rows="3" class="w-full bg-white border-gray-100 rounded-xl text-xs font-bold shadow-sm" placeholder="Phase 1: Design..."></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Maintenance & Support</label>
                                                <textarea v-model="form.maintenance_support" rows="3" class="w-full bg-white border-gray-100 rounded-xl text-xs font-bold shadow-sm" placeholder="Post-launch support period..."></textarea>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">⚖️ Terms & Conditions</label>
                                            <textarea v-model="form.terms_conditions" rows="3" class="w-full bg-white border-gray-100 rounded-xl text-xs font-bold shadow-sm" placeholder="Payment terms, legal clauses..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-6">
                                    <div class="bg-gray-50 p-8 rounded-[2rem] border border-gray-100">
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

                                    <div class="p-8 bg-indigo-900 rounded-[2rem] shadow-xl text-white">
                                        <h4 class="text-[10px] font-black uppercase tracking-widest mb-4 opacity-60 italic">AI Professional Suggestion</h4>
                                        <p class="text-[11px] leading-relaxed font-medium opacity-90">
                                            "Based on current tech stack ({{ project.technology_stack }}), we recommend emphasizing **End-to-End Encryption** and **Automated CI/CD** in the security section of the proposal."
                                        </p>
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

                <!-- Proposal Tab (Multi-Page Professional View) -->
                <div v-if="activeTab === 'proposal'" class="proposal-document">
                    
                    <!-- PAGE 1: COVER PAGE -->
                    <div class="page bg-white p-20 shadow-2xl rounded-[3rem] mb-10 flex flex-col justify-between min-h-[1000px] border-l-[16px] border-indigo-600 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-50 rounded-full blur-[100px] -mr-48 -mt-48 opacity-50"></div>
                        
                        <div>
                            <div class="bg-indigo-600 w-24 h-24 rounded-3xl mb-12 flex items-center justify-center shadow-xl rotate-3">
                                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <h1 class="text-6xl font-black text-gray-900 uppercase tracking-tighter leading-none mb-4">Enterprise<br/>Strategic<br/>Proposal</h1>
                            <div class="h-2 w-32 bg-indigo-600 mb-8 rounded-full"></div>
                            <p class="text-2xl font-bold text-indigo-700 uppercase tracking-widest mb-2">{{ project.name }}</p>
                            <p class="text-sm font-black text-gray-400 uppercase tracking-[0.3em]">Corporate Digital Initiative</p>
                        </div>

                        <div class="border-t border-gray-100 pt-16 grid grid-cols-2 gap-20">
                            <div>
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Client Representative</h4>
                                <p class="text-xl font-black text-gray-900 uppercase tracking-tight">{{ project.client_name || 'Valued Enterprise Client' }}</p>
                                <p class="text-xs font-bold text-gray-500 mt-1">Strategic Partner</p>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Proposal Issued By</h4>
                                <p class="text-xl font-black text-gray-900 uppercase tracking-tight">Project Dashboard v2</p>
                                <p class="text-xs font-bold text-gray-500 mt-1">Intelligence System • {{ formatDate(new Date()) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- PAGE 2: EXECUTIVE SUMMARY & TECHNICAL -->
                    <div class="page bg-white p-20 shadow-2xl rounded-[3rem] mb-10 min-h-[1000px] border border-gray-100">
                        <div class="flex items-center gap-4 mb-16 opacity-30">
                            <span class="text-[10px] font-black uppercase tracking-widest">Page 02</span>
                            <div class="h-px flex-1 bg-gray-200"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest">Confidential Initiative</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                            <section class="space-y-12">
                                <div>
                                    <h3 class="text-xs font-black text-indigo-700 uppercase tracking-[0.3em] mb-6">01. Executive Overview</h3>
                                    <p class="text-base font-medium text-gray-600 leading-relaxed text-justify">
                                        {{ project.description || 'This strategic initiative focuses on delivering a high-performance digital solution tailored to the specific needs of our client partners. Through agile methodology and cutting-edge technical architecture, we ensure maximum ROI and market impact.' }}
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-indigo-700 uppercase tracking-[0.3em] mb-6">02. Market Alignment</h3>
                                    <p class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-tight">Defining the Target Audience</p>
                                    <p class="text-sm font-medium text-gray-500 leading-relaxed italic">
                                        {{ project.target_audience || 'The primary stakeholders include decision-makers and end-users who require an intuitive, reliable, and scalable interface to manage complex data operations seamlessly.' }}
                                    </p>
                                </div>
                            </section>

                            <section class="bg-indigo-50/50 p-12 rounded-[3.5rem] border border-indigo-100/50">
                                <h3 class="text-xs font-black text-indigo-700 uppercase tracking-[0.3em] mb-10">03. Technical Infrastructure</h3>
                                <div class="space-y-8">
                                    <div class="flex items-start gap-4">
                                        <div class="w-8 h-8 rounded-xl bg-white flex items-center justify-center shadow-sm flex-shrink-0 text-indigo-600 font-black text-xs">#</div>
                                        <div>
                                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Stack Composition</p>
                                            <p class="text-xs font-bold text-gray-800 leading-relaxed">{{ project.technology_stack || 'Standard Enterprise Stack (PHP, SQL, React)' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4 pt-6 border-t border-indigo-100/50">
                                        <div class="w-8 h-8 rounded-xl bg-white flex items-center justify-center shadow-sm flex-shrink-0 text-indigo-600 font-black text-xs">S</div>
                                        <div>
                                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Architecture Scope</p>
                                            <p class="text-xs font-medium text-gray-600 leading-relaxed">{{ project.project_scope || 'Developing a robust backend with mobile-responsive frontend capabilities, integrated via secure RESTful APIs.' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-20 p-6 bg-white rounded-3xl border border-indigo-100 shadow-sm text-center">
                                    <p class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-2">Technical Feasibility</p>
                                    <p class="text-xs font-black text-gray-900 uppercase">Validated & Verified</p>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- PAGE 3: SECURITY & MILESTONES -->
                    <div class="page bg-gray-900 p-20 shadow-2xl rounded-[3rem] mb-10 min-h-[1000px] text-white relative overflow-hidden">
                        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[120px] -ml-64 -mb-64"></div>
                        
                        <div class="flex items-center gap-4 mb-20 opacity-30">
                            <span class="text-[10px] font-black uppercase tracking-widest">Page 03</span>
                            <div class="h-px flex-1 bg-white/10"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Security Standard v4.2</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                            <section>
                                <h3 class="text-xs font-black text-indigo-400 uppercase tracking-[0.3em] mb-8">04. Iron-Clad Security</h3>
                                <div class="space-y-6">
                                    <div class="p-8 bg-white/5 rounded-[2.5rem] border border-white/10 backdrop-blur-sm">
                                        <p class="text-[9px] font-black text-indigo-300 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Implementation Protocol
                                        </p>
                                        <p class="text-xs font-medium leading-relaxed opacity-80">
                                            {{ project.security_measures || 'We implement multi-layered security including AES-256 data encryption at rest, TLS 1.3 for data in transit, and role-based access control (RBAC) to ensure absolute data integrity.' }}
                                        </p>
                                    </div>
                                    <ul class="space-y-3 pl-4">
                                        <li class="flex items-center gap-3 text-[10px] font-bold opacity-60"><span class="w-1 h-1 bg-indigo-500 rounded-full"></span> OWASP Top 10 Compliance</li>
                                        <li class="flex items-center gap-3 text-[10px] font-bold opacity-60"><span class="w-1 h-1 bg-indigo-500 rounded-full"></span> Regular Automated Vulnerability Scanning</li>
                                        <li class="flex items-center gap-3 text-[10px] font-bold opacity-60"><span class="w-1 h-1 bg-indigo-500 rounded-full"></span> Secure Socket Layer (SSL/TLS) Integration</li>
                                    </ul>
                                </div>
                            </section>

                            <section>
                                <h3 class="text-xs font-black text-indigo-400 uppercase tracking-[0.3em] mb-8">05. Strategic Milestones</h3>
                                <div class="space-y-8 relative">
                                    <div class="absolute left-[3px] top-4 bottom-4 w-px bg-indigo-500/30"></div>
                                    
                                    <!-- Dynamic Milestones based on input or default -->
                                    <template v-if="project.milestones_content">
                                        <div class="relative pl-8 mb-8" v-for="(m, i) in project.milestones_content.split('\n')" :key="i">
                                            <div class="absolute left-0 top-1 w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,1)]"></div>
                                            <p class="text-xs font-black uppercase tracking-tight">{{ m }}</p>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="relative pl-8">
                                            <div class="absolute left-0 top-1 w-2 h-2 rounded-full bg-indigo-500"></div>
                                            <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Phase I • Strategic Discovery</p>
                                            <p class="text-xs font-medium opacity-60">Requirement gathering and analysis.</p>
                                        </div>
                                        <div class="relative pl-8">
                                            <div class="absolute left-0 top-1 w-2 h-2 rounded-full bg-indigo-500"></div>
                                            <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Phase II • Engineering & Dev</p>
                                            <p class="text-xs font-medium opacity-60">High-fidelity coding and internal testing.</p>
                                        </div>
                                        <div class="relative pl-8">
                                            <div class="absolute left-0 top-1 w-2 h-2 rounded-full bg-indigo-500"></div>
                                            <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Phase III • Deployment & QA</p>
                                            <p class="text-xs font-medium opacity-60">Production release and maintenance cycle.</p>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- PAGE 4: COMMERCIALS & TERMS -->
                    <div class="page bg-white p-20 shadow-2xl rounded-[3rem] min-h-[1000px] border border-gray-100 flex flex-col justify-between">
                         <div>
                            <div class="flex items-center gap-4 mb-20 opacity-30">
                                <span class="text-[10px] font-black uppercase tracking-widest">Page 04</span>
                                <div class="h-px flex-1 bg-gray-200"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest">Financial Framework</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                                <div>
                                    <h3 class="text-xs font-black text-indigo-700 uppercase tracking-[0.3em] mb-10">06. Resource Utilization</h3>
                                    <div class="bg-gray-50 p-10 rounded-[2.5rem] border border-gray-100">
                                        <div class="space-y-6">
                                            <div class="flex justify-between items-center pb-6 border-b border-gray-200">
                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Team Deployment</span>
                                                <span class="text-sm font-black text-gray-900">{{ project.team_size || 'N/A' }} Resources</span>
                                            </div>
                                            <div class="flex justify-between items-center pb-6 border-b border-gray-200">
                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Hourly Est.</span>
                                                <span class="text-sm font-black text-gray-900">{{ project.estimated_hours || 'N/A' }} Hours</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Proposed Investment</span>
                                                <span class="text-2xl font-black text-gray-900 tracking-tighter">${{ project.budget?.toLocaleString() || '0.00' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-10">
                                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Post-Launch Support</h4>
                                        <p class="text-xs font-medium text-gray-500 leading-relaxed italic border-l-4 border-indigo-100 pl-4">
                                            {{ project.maintenance_support || 'Standard 3-month complimentary support period for bug fixes and system optimization.' }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xs font-black text-indigo-700 uppercase tracking-[0.3em] mb-10">07. Legal Framework</h3>
                                    <div class="prose prose-sm text-gray-500 leading-relaxed text-[11px] space-y-4">
                                        <p class="font-black text-gray-800 uppercase text-[9px] tracking-widest">Terms & Conditions</p>
                                        <p class="text-justify font-medium">
                                            {{ project.terms_conditions || 'All intellectual property rights remain with the client upon final payment. Payments shall be made in installments tied to project milestones. This proposal is valid for 30 days from the date of issue.' }}
                                        </p>
                                    </div>
                                    <div class="mt-20 pt-10 border-t border-gray-100 flex items-center justify-between">
                                        <div class="w-24 h-px bg-gray-200"></div>
                                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-[0.4em]">Official Stamp Here</span>
                                        <div class="w-24 h-px bg-gray-200"></div>
                                    </div>
                                </div>
                            </div>
                         </div>

                         <div class="text-center opacity-20">
                             <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.5em]">End of Strategic Proposal</p>
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
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Live Team Assembly</h4>
                                    <div class="flex flex-wrap gap-4 mb-8">
                                        <div v-for="member in stats.team" :key="member.id" class="flex items-center gap-3 bg-white p-3 rounded-2xl border border-gray-100 shadow-sm">
                                            <img class="h-8 w-8 rounded-full border-2 border-indigo-50" :src="'https://ui-avatars.com/api/?name='+member.name+'&background=random'" alt="Member">
                                            <div class="flex flex-col">
                                                <span class="text-[9px] font-black text-gray-900 uppercase tracking-tight">{{ member.name }}</span>
                                                <span class="text-[8px] font-bold text-gray-400 uppercase">Contributor</span>
                                            </div>
                                        </div>
                                        <div v-if="stats.team.length === 0" class="text-[10px] font-black text-gray-300 uppercase italic">No team members assigned yet.</div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Handover Instructions</h4>
                                    <div class="bg-gray-900 text-indigo-100 p-8 rounded-3xl font-mono text-xs leading-relaxed whitespace-pre-wrap border-4 border-gray-800 shadow-inner">
                                        {{ project.handover_notes || "// No handover notes provided by the Super Admin yet." }}
                                    </div>
                                </div>
                                <div class="mt-8 pt-8 border-t border-gray-100">
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Current Roadmap</h4>
                                    <div class="space-y-4">
                                        <div v-for="task in project.tasks.slice(0, 5)" :key="task.id" class="flex items-center gap-4 p-4 rounded-2xl bg-gray-50/50 border border-gray-50 font-medium">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-[10px] font-black" 
                                                 :class="task.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                                                {{ task.status === 'completed' ? '✓' : '...' }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-gray-900 uppercase tracking-tight">{{ task.title }}</p>
                                                <p class="text-[9px] text-gray-400 font-bold uppercase">Assigned to: {{ task.assignee?.name || 'N/A' }}</p>
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

</template>

<style>
@media print {
    .no-print { display: none !important; }
    .page { 
        margin-bottom: 0 !important;
        box-shadow: none !important;
        border: none !important;
        page-break-after: always !important;
        min-height: 100vh !important;
        padding: 40px !important;
    }
    .page.bg-gray-900 {
        background-color: #111827 !important;
        -webkit-print-color-adjust: exact;
    }
    body { background: white !important; }
    .max-w-7xl { max-width: 100% !important; margin: 0 !important; }
    .p-20 { padding: 40px !important; }
}
</style>
