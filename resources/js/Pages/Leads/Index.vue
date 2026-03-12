<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    leads: Object,
    users: Array,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('leads.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showCreateModal = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    source: '',
    assigned_to: '',
    notes: '',
});

const createLead = () => {
    form.post(route('leads.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const updateStatus = (leadId, status) => {
    router.put(route('leads.status', leadId), { status: status }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Leads CRM | Intelligent Pipeline" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6">
                <div>
                    <h2 class="text-3xl font-black leading-tight text-gray-900 uppercase tracking-tighter">
                        Sales Force <span class="text-indigo-600">Command</span>
                    </h2>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mt-1">Intelligence-Driven Lead Lifecycle Management</p>
                </div>
                <button v-if="$page.props.auth.user.permissions.includes('manage leads') || $page.props.auth.user.roles.includes('Super Admin')" @click="showCreateModal = true" class="bg-gray-900 hover:bg-indigo-600 text-white px-8 py-3.5 rounded-2xl font-black shadow-xl shadow-indigo-100 transition-all flex items-center gap-3 text-[10px] uppercase tracking-widest active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Ingest Prospect
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Zoho-Style KPI Intelligence -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white border-2 border-gray-50 p-6 rounded-[2rem] shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
                        <div class="absolute -right-4 -top-8 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity">
                            <svg class="h-32 w-32 text-gray-900" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Pipeline</p>
                        <h3 class="text-4xl font-black text-gray-900">{{ stats.total }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[9px] font-black text-gray-400 uppercase tracking-tighter">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-200"></span> Global Database
                        </div>
                    </div>

                    <div class="bg-white border-2 border-gray-50 p-6 rounded-[2rem] shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
                        <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Incoming Velocity</p>
                        <h3 class="text-4xl font-black text-gray-900">{{ stats.new }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[9px] font-black text-indigo-600 uppercase tracking-tighter bg-indigo-50 w-max px-2 py-0.5 rounded-lg">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span> New Requests
                        </div>
                    </div>

                    <div class="bg-white border-2 border-gray-50 p-6 rounded-[2rem] shadow-sm hover:shadow-xl transition-all group overflow-hidden relative border-amber-100">
                        <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">Filtered Leads</p>
                        <h3 class="text-4xl font-black text-gray-900">{{ stats.qualified }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[9px] font-black text-amber-600 uppercase tracking-tighter">
                             Verified Potential
                        </div>
                    </div>

                    <div class="bg-indigo-600 p-6 rounded-[2rem] shadow-xl shadow-indigo-100 group overflow-hidden relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>
                        <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-1">Conversion Alpha</p>
                        <h3 class="text-4xl font-black text-white">{{ stats.conversion_rate }}%</h3>
                        <div class="mt-4 flex items-center gap-2 text-[9px] font-black text-indigo-100 uppercase tracking-tighter">
                             Success Metrics
                        </div>
                    </div>

                    <div class="bg-white border-2 border-emerald-50 p-6 rounded-[2rem] shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Closed Deals</p>
                        <h3 class="text-4xl font-black text-gray-900">{{ stats.won }}</h3>
                        <div class="mt-4 flex items-center gap-2 text-[9px] font-black text-emerald-600 uppercase tracking-tighter bg-emerald-50 w-max px-2 py-0.5 rounded-lg">
                             Won Status
                        </div>
                    </div>
                </div>
                
                <DataTable 
                    :headers="[
                        { key: 'name', label: 'Lead Identity', sortable: true },
                        { key: 'contact', label: 'Contact Profile' },
                        { key: 'assignee', label: 'Sales Officer' },
                        { key: 'status', label: 'Lead Stage' }
                    ]"
                    :items="leads.data"
                    placeholder="Search by identity, email or company..."
                    @search="val => search = val"
                >
                    <template #row="{ item: lead }">
                        <td class="px-6 py-6">
                            <div class="flex items-center">
                                <div class="h-12 w-12 flex-shrink-0 relative">
                                    <img class="h-12 w-12 rounded-[1.2rem] border-2 border-white shadow-md" :src="'https://ui-avatars.com/api/?name='+lead.name+'&background=F3F4F6&color=374151'" alt="Avatar">
                                    <div class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <div class="w-2.5 h-2.5 rounded-full" :class="lead.status === 'won' ? 'bg-emerald-500' : 'bg-indigo-400'"></div>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ lead.name }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5" v-if="lead.company">{{ lead.company }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex flex-col gap-1.5">
                                <div class="text-[11px] font-bold text-gray-700 flex items-center gap-2">
                                     <span class="p-1 bg-gray-50 rounded-lg"><svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></span>
                                     {{ lead.email || 'NO_IDENTIFIER' }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-bold flex items-center gap-2">
                                     <span class="p-1 bg-gray-50 rounded-lg"><svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg></span>
                                     {{ lead.phone || 'NO_PH_DATA' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 bg-gray-100 rounded-xl flex items-center justify-center text-[10px] font-black text-gray-400 overflow-hidden border border-gray-100">
                                    <img v-if="lead.assignee" :src="'https://ui-avatars.com/api/?name='+lead.assignee.name+'&background=random'" alt="Avatar">
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14v7m-3-3h6"/></svg>
                                </div>
                                <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest">{{ lead.assignee ? lead.assignee.name : 'Pipeline Free' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap text-right">
                             <div class="flex items-center justify-end gap-3">
                                 <button @click="showCreateModal = true" class="p-2.5 bg-gray-50 text-gray-400 hover:bg-indigo-600 hover:text-white rounded-[1rem] border border-gray-100 transition-all shadow-sm active:scale-95 group/follow">
                                     <svg class="w-4 h-4 group-hover/follow:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                 </button>
                                 <select v-model="lead.status" @change="updateStatus(lead.id, $event.target.value)" class="text-[9px] font-black uppercase tracking-[0.2em] border-2 rounded-2xl px-5 py-2.5 transition-all cursor-pointer shadow-md border-transparent focus:ring-0" :class="{
                                    'bg-sky-50 text-sky-700': lead.status === 'new',
                                    'bg-purple-50 text-purple-700': lead.status === 'contacted',
                                    'bg-amber-50 text-amber-700': lead.status === 'qualified',
                                    'bg-indigo-50 text-indigo-700': lead.status === 'proposal',
                                    'bg-emerald-50 text-emerald-700': lead.status === 'won',
                                    'bg-rose-50 text-rose-700': lead.status === 'lost'
                                }">
                                    <option value="new">🆕 New</option>
                                    <option value="contacted">📞 Contact</option>
                                    <option value="qualified">✔️ Qualified</option>
                                    <option value="proposal">📝 Proposal</option>
                                    <option value="won">🎉 Won</option>
                                    <option value="lost">❌ Lost</option>
                                </select>
                             </div>
                        </td>
                    </template>
                </DataTable>

                <Pagination :links="leads.links" class="mt-8" />
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false" title="Ingest New Prospect" maxWidth="2xl">
            <form @submit.prevent="createLead" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Identity Profile (Name)</label>
                        <input v-model="form.name" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required placeholder="John Smith..." />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Organization / Entity</label>
                        <input v-model="form.company" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" placeholder="Enterprise Co..." />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Communications (Email)</label>
                        <input v-model="form.email" type="email" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required placeholder="john@enterprise.com" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Direct Line (Phone)</label>
                        <input v-model="form.phone" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" placeholder="+1..." />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Inflow Source</label>
                        <select v-model="form.source" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5">
                            <option value="">Undisclosed</option>
                            <option value="website">Corporate Website</option>
                            <option value="referral">Internal Referral</option>
                            <option value="social">Intelligence (Social)</option>
                            <option value="cold_call">Cold Outreach</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Executive Assignment</label>
                        <select v-model="form.assigned_to" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5">
                            <option value="">Global Pipeline</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Strategic Intel (Notes)</label>
                        <textarea v-model="form.notes" rows="3" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner placeholder-gray-300" placeholder="Specific requirements or budget constraints..."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4">
                    <button type="button" @click="showCreateModal = false" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all active:scale-95">Discard</button>
                    <button type="submit" :disabled="form.processing" class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95">Ingest Prospect</button>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>
