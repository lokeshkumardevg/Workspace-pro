<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    leads: Object,
    users: Array,
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
    <Head title="Leads CRM" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Lead CRM Manager
                </h2>
                <button v-if="$page.props.auth.user.permissions.includes('manage leads') || $page.props.auth.user.roles.includes('Super Admin')" @click="showCreateModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Lead
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Table Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    
                    <!-- Toolbar -->
                    <div class="p-6 bg-white border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-gray-900 font-semibold text-lg">Sales & Inquiries</div>
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Search by name, email or company..." class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm transition-colors text-gray-700" />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Lead Identity</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact Profile</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sales Rep</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Lead Stage (Status)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img class="h-10 w-10 rounded-full border border-gray-200" :src="'https://ui-avatars.com/api/?name='+lead.name+'&background=F3F4F6&color=374151'" alt="Avatar">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ lead.name }}</div>
                                                <div class="text-xs text-gray-500" v-if="lead.company">{{ lead.company }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 flex items-center gap-1"><svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>{{ lead.email || '-' }}</div>
                                        <div class="text-sm text-gray-500 flex items-center gap-1 mt-1"><svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>{{ lead.phone || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <img v-if="lead.assignee" class="h-6 w-6 rounded-full border border-gray-200" :src="'https://ui-avatars.com/api/?name='+lead.assignee.name+'&background=random'" alt="Avatar">
                                            <img v-else class="h-6 w-6 rounded-full border border-gray-200 bg-gray-100" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ccc'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14v7m-3-3h6'/%3E%3C/svg%3E" alt="Unassigned">
                                            {{ lead.assignee ? lead.assignee.name : 'Unassigned' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <select v-model="lead.status" @change="updateStatus(lead.id, $event.target.value)" class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-semibold w-32" :class="{
                                            'bg-sky-50 text-sky-700': lead.status === 'new',
                                            'bg-purple-50 text-purple-700': lead.status === 'contacted',
                                            'bg-amber-50 text-amber-700': lead.status === 'qualified',
                                            'bg-indigo-50 text-indigo-700': lead.status === 'proposal',
                                            'bg-emerald-50 text-emerald-700': lead.status === 'won',
                                            'bg-rose-50 text-rose-700': lead.status === 'lost'
                                        }">
                                            <option value="new">🆕 New</option>
                                            <option value="contacted">📞 Contacted</option>
                                            <option value="qualified">✔️ Qualified</option>
                                            <option value="proposal">📝 Proposal</option>
                                            <option value="won">🎉 Won</option>
                                            <option value="lost">❌ Lost</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr v-if="leads.data.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500 font-medium">No leads match your criteria.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="leads.links" class="mt-6" />
            </div>
        </div>

        <!-- Add Lead Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="showCreateModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full">
                    <form @submit.prevent="createLead">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-xl leading-6 font-bold text-gray-900 mb-4" id="modal-title">Create Lead Record</h3>
                            <div class="space-y-4">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                        <input v-model="form.name" type="text" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required placeholder="John Smith" />
                                    </div>
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                                        <input v-model="form.company" type="text" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Acme Corp" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                        <input v-model="form.email" type="email" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" required placeholder="john@example.com" />
                                    </div>
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                        <input v-model="form.phone" type="text" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="+1..." />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                                        <select v-model="form.source" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            <option value="" disabled>Select Source</option>
                                            <option value="website">Website Form</option>
                                            <option value="referral">Referral</option>
                                            <option value="social">Social Media</option>
                                            <option value="cold_call">Cold Call</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Assign Representative</label>
                                        <select v-model="form.assigned_to" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            <option value="" disabled>Assign To...</option>
                                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea v-model="form.notes" rows="2" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Requirements, budget..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse rounded-b-2xl">
                            <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Import Lead
                            </button>
                            <button type="button" @click="showCreateModal = false" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
