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
    { value: 'sick',      label: 'Sick Leave' },
    { value: 'casual',   label: 'Casual Leave' },
    { value: 'earned',   label: 'Earned Leave' },
    { value: 'maternity',label: 'Maternity Leave' },
    { value: 'unpaid',   label: 'Unpaid Leave' },
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

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(item, i) in [
                    { label: 'Total Requests', value: stats.total,    color: 'indigo', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
                    { label: 'Pending', value: stats.pending,  color: 'amber',  icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label: 'Approved',       value: stats.approved, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label: 'Rejected',           value: stats.rejected, color: 'rose',    icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
                ]" :key="i"
                    class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm flex items-center gap-6">
                    <div class="p-3 rounded-2xl" :class="{
                        'bg-indigo-50 text-indigo-600': item.color === 'indigo',
                        'bg-amber-50 text-amber-600': item.color === 'amber',
                        'bg-emerald-50 text-emerald-600': item.color === 'emerald',
                        'bg-rose-50 text-rose-600': item.color === 'rose',
                    }">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" /></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ item.label }}</p>
                        <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ item.value }}</p>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <DataTable 
                :headers="[
                    { key: 'user', label: 'Employee' },
                    { key: 'type', label: 'Leave Type' },
                    { key: 'duration', label: 'Date Range' },
                    { key: 'days', label: 'Total Days' },
                    { key: 'status', label: 'Status' },
                    { key: 'reviewer', label: 'Reviewed By' },
                    { key: 'actions', label: 'Actions' }
                ]"
                :items="leaves.data"
                placeholder="Search leaves..."
                @search="val => search = val"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-3">
                         <select v-model="filterStatus" class="border-gray-200 bg-gray-50 rounded-xl text-xs font-bold px-4 py-2 focus:ring-indigo-500 outline-none">
                            <option value="all">All Requests</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <a v-if="$page.props.auth.user.roles.includes('Super Admin') || $page.props.auth.user.roles.includes('Admin')"
                           :href="route('leaves.download')"
                           class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-xl px-4 py-2 text-xs transition-all shadow-sm active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export
                        </a>
                        <button @click="showModal = true" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl px-5 py-2.5 text-xs transition-all shadow-md active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Apply Leave
                        </button>
                    </div>
                </template>

                <template #row="{ item: leave }">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img class="h-10 w-10 rounded-full border border-gray-100 shadow-sm" :src="'https://ui-avatars.com/api/?name='+leave.user.name+'&background=random'" alt="" />
                            <div class="text-left">
                                <p class="text-sm font-bold text-gray-900">{{ leave.user.name }}</p>
                                <p class="text-[10px] text-gray-400 font-medium italic">{{ leave.user.email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-3 py-1 bg-gray-50 text-gray-600 border border-gray-100 rounded-lg text-[10px] font-bold uppercase">
                            {{ leave.type }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2 text-[11px] font-bold text-gray-700">
                                <span class="text-indigo-600">{{ formatDate(leave.from_date) }}</span>
                                <span class="text-gray-300">→</span>
                                <span class="text-indigo-600">{{ formatDate(leave.to_date) }}</span>
                            </div>
                            <p class="text-[10px] text-gray-400 font-medium italic truncate max-w-[200px]" :title="leave.reason">{{ leave.reason }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-gray-900">{{ leave.days }} <span class="text-[10px] text-gray-400 font-normal uppercase">Days</span></div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 inline-flex text-[10px] font-bold rounded-full uppercase border shadow-sm" :class="statusClass(leave.status)">
                            {{ leave.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div v-if="leave.reviewer" class="flex items-center gap-2">
                             <img class="w-6 h-6 rounded-full border border-gray-100" :src="'https://ui-avatars.com/api/?name='+leave.reviewer.name+'&background=random'" alt="" />
                             <div class="text-left">
                                <p class="text-[10px] font-bold text-gray-700 uppercase">{{ leave.reviewer.name }}</p>
                                <p v-if="leave.review_note" class="text-[8px] text-gray-400 font-medium italic truncate max-w-[120px]">{{ leave.review_note }}</p>
                             </div>
                        </div>
                        <span v-else class="text-[10px] text-gray-300 italic">Not Reviewed</span>
                    </td>
                    <td class="px-6 py-4">
                        <div v-if="leave.status === 'pending' && ($page.props.auth.user.permissions.includes('approve leaves') || $page.props.auth.user.roles.includes('Super Admin'))"
                             class="flex items-center gap-2">
                            <button @click="openReview(leave, 'approved')"
                                class="bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition-all shadow-sm active:scale-90 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Approve
                            </button>
                            <button @click="openReview(leave, 'rejected')"
                                class="bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition-all shadow-sm active:scale-90 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject
                            </button>
                        </div>
                        <div v-else class="text-right">
                            <span v-if="leave.status !== 'pending'" class="text-[10px] text-gray-400 italic">Action Completed</span>
                            <span v-else class="text-[10px] text-amber-500 font-bold uppercase flex items-center justify-end gap-1">
                                <span class="h-1.5 w-1.5 bg-amber-500 rounded-full animate-pulse"></span> Waiting
                            </span>
                        </div>
                    </td>
                </template>
            </DataTable>

            <div class="flex justify-end pr-4 mt-8">
                 <Pagination :links="leaves.links" />
            </div>
        </div>

        <!-- Apply Leave Modal -->
        <Modal :show="showModal" @close="showModal = false" title="Apply for Leave" maxWidth="xl">
            <form @submit.prevent="submitLeave" class="p-6 space-y-6">
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Leave Type</label>
                        <select v-model="form.type" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3" required>
                            <option v-for="t in leaveTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <p v-if="form.errors.type" class="text-rose-500 text-[10px] mt-1 ml-1">{{ form.errors.type }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">From Date</label>
                            <input v-model="form.from_date" type="date" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3" required />
                            <p v-if="form.errors.from_date" class="text-rose-500 text-[10px] mt-1 ml-1">{{ form.errors.from_date }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">To Date</label>
                            <input v-model="form.to_date" type="date" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3" required />
                            <p v-if="form.errors.to_date" class="text-rose-500 text-[10px] mt-1 ml-1">{{ form.errors.to_date }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Reason</label>
                        <textarea v-model="form.reason" rows="4" placeholder="Enter reason for leave..." class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3 resize-none" required></textarea>
                        <p v-if="form.errors.reason" class="text-rose-500 text-[10px] mt-1 ml-1">{{ form.errors.reason }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-6 py-2 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 transition-all active:scale-95">
                        Submit Request
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Review Modal -->
        <Modal :show="!!reviewModal" @close="reviewModal = null" :title="reviewModal?.action === 'approved' ? 'Approve Leave' : 'Reject Leave'" maxWidth="md">
            <form @submit.prevent="submitReview" class="p-6 space-y-6" v-if="reviewModal">
                <div class="p-6 rounded-2xl flex flex-col items-center text-center transition-all bg-gray-50 border border-gray-100">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-md mb-4"
                         :class="reviewModal.action === 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path v-if="reviewModal.action === 'approved'" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ reviewModal.action === 'approved' ? 'Confirm Approval' : 'Confirm Rejection' }}</h3>
                        <p class="text-xs text-gray-400 mt-2">Employee: <span class="text-indigo-600 font-bold">{{ reviewModal.leave.user.name }}</span></p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-3 ml-1">Review Note (Optional)</label>
                    <textarea v-model="reviewForm.review_note" rows="4" placeholder="Enter a note for the employee..."
                              class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3 resize-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="button" @click="reviewModal = null" class="flex-1 px-6 py-2.5 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit" :disabled="reviewForm.processing"
                            class="flex-1 px-8 py-2.5 font-bold rounded-xl text-sm text-white transition-all shadow-lg active:scale-95"
                            :class="reviewModal.action === 'approved' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100' : 'bg-rose-600 hover:bg-rose-700 shadow-rose-100'">
                        {{ reviewForm.processing ? 'Saving...' : (reviewModal.action === 'approved' ? 'Approve' : 'Reject') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
