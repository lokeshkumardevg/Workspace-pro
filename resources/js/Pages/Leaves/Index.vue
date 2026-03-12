<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    leaves:  Object,
    stats:   Object,
    filters: Object,
});

const search      = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || 'all');
const showModal   = ref(false);
const reviewModal = ref(null); // { leave, action }

const isPrivileged = computed(() => {
    const roles = window.__page?.props?.auth?.user?.roles || [];
    return false; // actual check done server-side
});

watch([search, filterStatus], debounce(() => {
    router.get(route('leaves.index'), {
        search: search.value,
        status: filterStatus.value,
    }, { preserveState: true, replace: true });
}, 350));

// Apply form
const form = useForm({
    type:      'casual',
    from_date: '',
    to_date:   '',
    reason:    '',
});

const submitLeave = () => {
    form.post(route('leaves.store'), {
        onSuccess: () => { showModal.value = false; form.reset(); }
    });
};

// Review form
const reviewForm = useForm({ action: '', review_note: '' });

const openReview = (leave, action) => {
    reviewModal.value = { leave, action };
    reviewForm.reset();
    reviewForm.action = action;
};

const submitReview = () => {
    if (!reviewModal.value) return;
    reviewForm.post(route('leaves.review', reviewModal.value.leave.id), {
        onSuccess: () => { reviewModal.value = null; reviewForm.reset(); }
    });
};

const leaveTypes = [
    { value: 'sick',      label: '🤒 Sick Leave' },
    { value: 'casual',   label: '🌴 Casual Leave' },
    { value: 'earned',   label: '⭐ Earned Leave' },
    { value: 'maternity',label: '👶 Maternity Leave' },
    { value: 'unpaid',   label: '💸 Unpaid Leave' },
];

const formatDate = (d) => {
    if (!d) return '—';
    const date = new Date(d);
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
};

const statusClass = (s) => ({
    'pending':  'bg-amber-100 text-amber-800 border border-amber-200',
    'approved': 'bg-green-100 text-green-800 border border-green-200',
    'rejected': 'bg-red-100 text-red-800 border border-red-200',
}[s] || '');
</script>

<template>
    <Head title="Leave Management" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-900">Leave Management</h2>
        </template>

        <div class="py-10 px-4 lg:px-8 space-y-8">

            <!-- High Fidelity Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(item, i) in [
                    { label: 'Fleet Requests', value: stats.total,    color: 'indigo', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
                    { label: 'Active Pipeline', value: stats.pending,  color: 'amber',  icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label: 'Authorized',       value: stats.approved, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label: 'Vetoed',           value: stats.rejected, color: 'rose',    icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
                ]" :key="i"
                    class="bg-white border border-gray-100 rounded-[2.5rem] p-8 shadow-sm flex items-center gap-6 transition-transform hover:scale-[1.02]">
                    <div class="p-4 rounded-3xl" :class="{
                        'bg-indigo-50 text-indigo-600': item.color === 'indigo',
                        'bg-amber-50 text-amber-600': item.color === 'amber',
                        'bg-emerald-50 text-emerald-600': item.color === 'emerald',
                        'bg-rose-50 text-rose-600': item.color === 'rose',
                    }">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="item.icon" /></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ item.label }}</p>
                        <p class="text-3xl font-black text-gray-900 mt-1">{{ item.value }}</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced DataTable -->
            <DataTable 
                :headers="[
                    { key: 'user', label: 'Employee Profile', sortable: true },
                    { key: 'type', label: 'Category' },
                    { key: 'duration', label: 'Strategic Window' },
                    { key: 'days', label: 'Units', sortable: true },
                    { key: 'status', label: 'Authorization' },
                    { key: 'reviewer', label: 'Review Authority' },
                    { key: 'actions', label: 'Governance' }
                ]"
                :items="leaves.data"
                placeholder="Search by identity or request reason..."
                @search="val => search = val"
            >
                <template #toolbar-extra>
                    <div class="flex items-center gap-3">
                         <select v-model="filterStatus" class="border-transparent bg-gray-50 rounded-2xl text-[9px] font-black uppercase tracking-widest px-6 py-3.5 focus:ring-4 focus:ring-indigo-50 outline-none shadow-inner">
                            <option value="all">All Channels</option>
                            <option value="pending">⏳ Action Required</option>
                            <option value="approved">✅ Authorized</option>
                            <option value="rejected">🛑 Declined</option>
                        </select>
                        <a v-if="$page.props.auth.user.permissions.includes('download reports') || $page.props.auth.user.roles.includes('Super Admin')"
                           :href="route('leaves.download')"
                           class="inline-flex items-center gap-3 bg-gray-900 hover:bg-indigo-600 text-white font-black rounded-2xl px-6 py-3.5 text-[9px] uppercase tracking-[0.2em] transition-all shadow-xl active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export Assets
                        </a>
                        <button @click="showModal = true" class="inline-flex items-center gap-3 bg-indigo-600 hover:bg-gray-950 text-white font-black rounded-2xl px-8 py-3.5 text-[9px] uppercase tracking-[0.2em] transition-all shadow-xl shadow-indigo-100 active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Dispatch Request
                        </button>
                    </div>
                </template>

                <template #row="{ item: leave }">
                    <td class="px-6 py-6">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 flex-shrink-0 relative">
                                <img class="h-12 w-12 rounded-[1.2rem] border-2 border-white shadow-md" :src="'https://ui-avatars.com/api/?name='+leave.user.name+'&background=random'" alt="" />
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <div class="w-2.5 h-2.5 rounded-full" :class="statusClass(leave.status).includes('green') ? 'bg-emerald-500' : (statusClass(leave.status).includes('amber') ? 'bg-amber-400' : 'bg-rose-400')"></div>
                                </div>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ leave.user.name }}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ leave.user.email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <span class="inline-flex px-4 py-1.5 bg-gray-50 text-gray-600 border border-gray-100 rounded-xl text-[9px] font-black uppercase tracking-[0.2em]">
                            {{ leave.type }}
                        </span>
                    </td>
                    <td class="px-6 py-6">
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-3 text-[11px] font-black text-gray-800">
                                <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg border border-indigo-100">{{ formatDate(leave.from_date) }}</span>
                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg border border-indigo-100">{{ formatDate(leave.to_date) }}</span>
                            </div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest line-clamp-1 italic max-w-[200px]" :title="leave.reason">{{ leave.reason }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-6 whitespace-nowrap">
                        <div class="text-lg font-black text-gray-900">{{ leave.days }}<span class="text-[10px] text-gray-400 uppercase tracking-widest ml-1">Units</span></div>
                    </td>
                    <td class="px-6 py-6">
                        <span class="px-4 py-2 inline-flex text-[9px] font-black rounded-2xl uppercase tracking-[0.2em] shadow-sm" :class="statusClass(leave.status)">
                            {{ leave.status }}
                        </span>
                    </td>
                    <td class="px-6 py-6">
                        <div v-if="leave.reviewer" class="flex items-center gap-3">
                             <img class="w-7 h-7 rounded-lg border border-gray-100 shadow-inner" :src="'https://ui-avatars.com/api/?name='+leave.reviewer.name+'&background=random'" alt="" />
                             <div class="text-left">
                                <p class="text-[10px] font-black text-gray-700 uppercase tracking-tight">{{ leave.reviewer.name }}</p>
                                <p v-if="leave.review_note" class="text-[8px] text-gray-400 font-bold uppercase tracking-widest mt-0.5 line-clamp-1 italic max-w-[120px]">{{ leave.review_note }}</p>
                             </div>
                        </div>
                        <span v-else class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic leading-none flex items-center gap-2">
                             <span class="w-1 h-4 bg-gray-100 rounded-full"></span> Unprocessed
                        </span>
                    </td>
                    <td class="px-6 py-6">
                        <div v-if="leave.status === 'pending' && ($page.props.auth.user.permissions.includes('approve leaves') || $page.props.auth.user.roles.includes('Super Admin'))"
                             class="flex items-center gap-2">
                            <button @click="openReview(leave, 'approved')"
                                class="bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-90 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Authorize
                            </button>
                            <button @click="openReview(leave, 'rejected')"
                                class="bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-90 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Veto
                            </button>
                        </div>
                        <div v-else class="flex flex-col items-end">
                            <span v-if="leave.status !== 'pending'" class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] italic">Governance Finalized</span>
                            <span v-else class="text-[9px] font-black text-amber-600 uppercase tracking-[0.2em] flex items-center gap-2">
                                <span class="h-1.5 w-1.5 bg-amber-500 rounded-full animate-pulse"></span> Security Hold
                            </span>
                        </div>
                    </td>
                </template>
            </DataTable>

            <div class="mt-8">
                 <Pagination :links="leaves.links" />
            </div>
        </div>

        <!-- Apply Leave Modal -->
        <Modal :show="showModal" @close="showModal = false" title="Strategic Leave Dispatch" maxWidth="xl">
            <form @submit.prevent="submitLeave" class="space-y-8">
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Operational Channel (Type)</label>
                        <select v-model="form.type" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                            <option v-for="t in leaveTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <p v-if="form.errors.type" class="text-rose-500 text-[10px] font-black uppercase mt-2 ml-1">{{ form.errors.type }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Initiation Point</label>
                            <input v-model="form.from_date" type="date" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" required />
                            <p v-if="form.errors.from_date" class="text-rose-500 text-[10px] font-black uppercase mt-2 ml-1">{{ form.errors.from_date }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Termination Point</label>
                            <input v-model="form.to_date" type="date" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" required />
                            <p v-if="form.errors.to_date" class="text-rose-500 text-[10px] font-black uppercase mt-2 ml-1">{{ form.errors.to_date }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Submission Context (Reason)</label>
                        <textarea v-model="form.reason" rows="4" placeholder="Brief technical justification for the absence..." class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner placeholder-gray-300 py-4 resize-none" required></textarea>
                        <p v-if="form.errors.reason" class="text-rose-500 text-[10px] font-black uppercase mt-2 ml-1">{{ form.errors.reason }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" @click="showModal = false" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">Abort</button>
                    <button type="submit" :disabled="form.processing" class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95">
                        {{ form.processing ? 'Syncing...' : 'Dispatch Protocol' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Review Governance Modal (Approve/Reject) -->
        <Modal :show="!!reviewModal" @close="reviewModal = null" :title="reviewModal?.action === 'approved' ? 'Authorization Protocol' : 'Veto Protocol'" maxWidth="md">
            <form @submit.prevent="submitReview" class="space-y-8" v-if="reviewModal">
                <div class="p-8 rounded-[2.5rem] border border-gray-100 flex flex-col items-center text-center transition-all bg-gray-50" :class="reviewModal.action === 'approved' ? 'bg-emerald-50/30' : 'bg-rose-50/30'">
                    <div class="w-20 h-20 rounded-[1.8rem] flex items-center justify-center shadow-xl mb-6 transform transition-transform hover:rotate-12"
                         :class="reviewModal.action === 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path v-if="reviewModal.action === 'approved'" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter leading-none">{{ reviewModal.action === 'approved' ? 'Confirm Authorization' : 'Authorize Veto' }}</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mt-3">Target: <span class="text-indigo-600">{{ reviewModal.leave.user.name }}</span></p>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 ml-1">Governance Directive (Note)</label>
                    <textarea v-model="reviewForm.review_note" rows="4" :placeholder="reviewModal.action === 'approved' ? 'Strategic authorization parameters...' : 'Operational rejection logic...'"
                              class="w-full bg-gray-50 border-transparent rounded-[1.8rem] focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 resize-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="button" @click="reviewModal = null" class="flex-1 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all border border-transparent hover:bg-gray-50">Cancel</button>
                    <button type="submit" :disabled="reviewForm.processing"
                            class="flex-1 px-8 py-4 font-black rounded-2xl uppercase tracking-widest text-[10px] text-white transition-all shadow-xl active:scale-95"
                            :class="reviewModal.action === 'approved' ? 'bg-emerald-600 hover:bg-gray-950 shadow-emerald-100' : 'bg-rose-600 hover:bg-gray-950 shadow-rose-100'">
                        {{ reviewForm.processing ? 'Processing...' : (reviewModal.action === 'approved' ? 'Commit Authorization' : 'Commit Veto') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>
