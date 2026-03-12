<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    projects: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('projects.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showCreateModal = ref(false);

const form = useForm({
    name: '',
    client_name: '',
    budget: '',
    technology_stack: '',
    estimated_hours: '',
    team_size: '',
    description: '',
    start_date: '',
    end_date: '',
});

const createProject = () => {
    form.post(route('projects.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Project Management
                </h2>
                <button v-if="$page.props.auth.user.permissions.includes('manage projects') || $page.props.auth.user.roles.includes('Super Admin')" @click="showCreateModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Project
                </button>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <DataTable 
                    :headers="[
                        { key: 'name', label: 'Project Name', sortable: true },
                        { key: 'timeline', label: 'Timeline' },
                        { key: 'status', label: 'Status' },
                        { key: 'actions', label: 'Actions / Insights' }
                    ]"
                    :items="projects.data"
                    placeholder="Search by name, client, or technology..."
                    @search="val => search = val"
                >
                    <template #actions>
                        <div class="flex items-center gap-2">
                             <div class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-indigo-100">
                                 Total: {{ projects.total }} Projects
                             </div>
                        </div>
                    </template>

                    <template #row="{ item: project }">
                        <td class="px-6 py-6">
                            <div class="flex flex-col">
                                <Link :href="route('projects.show', project.id)" class="text-sm font-black text-gray-900 hover:text-indigo-600 transition-colors uppercase tracking-tight">{{ project.name }}</Link>
                                <div class="text-[11px] text-gray-400 font-bold truncate max-w-xs mt-1 uppercase">{{ project.client_name || 'Internal Development' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ project.start_date || 'TBD' }} 
                                </div>
                                <div class="h-0.5 w-4 bg-gray-100"></div>
                                <div class="flex items-center gap-2 text-[10px] font-black text-gray-900 uppercase tracking-widest">
                                    <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ project.end_date || 'Ongoing' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <span :class="{
                                'bg-emerald-50 text-emerald-700 border-emerald-100': project.status === 'completed',
                                'bg-indigo-50 text-indigo-700 border-indigo-100': project.status === 'in_progress',
                                'bg-amber-50 text-amber-700 border-amber-100': project.status === 'pending'
                            }" class="px-4 py-1.5 inline-flex text-[9px] leading-5 font-black rounded-full border uppercase tracking-[0.2em]">
                                {{ project.status?.replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex items-center justify-end gap-3">
                                <div class="hidden md:flex flex-col items-end mr-4">
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Creator</span>
                                    <span class="text-[10px] font-bold text-gray-700">{{ project.creator.name }}</span>
                                </div>
                                <Link v-if="$page.props.auth.user.roles.some(r => ['Super Admin', 'Admin'].includes(r))" 
                                      :href="route('projects.show', project.id)" 
                                      class="inline-flex items-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-900 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-95 border border-gray-100">
                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    Review
                                </Link>
                                <div v-if="$page.props.auth.user.roles.includes('Super Admin')" class="flex items-center gap-2">
                                     <Link :href="route('projects.show', { project: project.id, tab: 'proposal' })" 
                                           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-gray-950 text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 group/btn">
                                        <svg class="w-3.5 h-3.5 group-hover/btn:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        Proposal
                                     </Link>
                                     <Link :href="route('projects.show', { project: project.id, tab: 'report' })" 
                                           class="inline-flex items-center gap-2 bg-gray-900 hover:bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 group/btn">
                                        <svg class="w-3.5 h-3.5 group-hover/btn:-translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Handover
                                     </Link>
                                </div>
                            </div>
                        </td>
                    </template>
                </DataTable>

                <Pagination :links="projects.links" class="mt-8" />
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false" title="Initialize New Venture" maxWidth="2xl">
            <form @submit.prevent="createProject" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Venture Integrity (Name)</label>
                        <input v-model="form.name" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5 placeholder-gray-300" placeholder="e.g. Project Apollo..." required />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Conceptual Blueprint (Description)</label>
                        <textarea v-model="form.description" rows="3" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner placeholder-gray-300" placeholder="Detailed vision for this project..."></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Launch Sequence (Start)</label>
                        <input v-model="form.start_date" type="date" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Target Achievement (End)</label>
                        <input v-model="form.end_date" type="date" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" />
                    </div>
                </div>

                <div v-if="$page.props.auth.user.roles.some(r => ['Super Admin', 'Admin'].includes(r))" class="p-8 bg-indigo-50/50 rounded-[2.5rem] border border-indigo-100/50 space-y-6">
                    <h4 class="text-xs font-black text-indigo-700 uppercase tracking-[0.3em] flex items-center gap-3">
                        <span class="p-1.5 bg-indigo-600 rounded-lg"><svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></span>
                        Commercial Authorization
                    </h4>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2 ml-1">Partner / Client</label>
                            <input v-model="form.client_name" type="text" class="w-full bg-white border-transparent rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 text-xs font-bold shadow-sm" placeholder="Global Corp Ltd." />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2 ml-1">Investment Val.</label>
                            <input v-model="form.budget" type="number" step="0.01" class="w-full bg-white border-transparent rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 text-xs font-bold shadow-sm placeholder-mono" placeholder="50,000.00" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2 ml-1">Technical Foundation (Stack)</label>
                        <input v-model="form.technology_stack" type="text" class="w-full bg-white border-transparent rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 text-xs font-bold shadow-sm" placeholder="Laravel, Vue, AWS, AI Engine" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4">
                    <button type="button" @click="showCreateModal = false" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all active:scale-95">Abandon</button>
                    <button type="submit" :disabled="form.processing" class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-200 active:scale-95">Deploy Venture</button>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>
