<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
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

        <div class="py-6 px-4 lg:px-8 space-y-5">

            <!-- Stats Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="(item, i) in [
                    { label: 'Total Requests', value: stats.total,    color: 'blue',   icon: '📋' },
                    { label: 'Pending',        value: stats.pending,  color: 'amber',  icon: '⏳' },
                    { label: 'Approved',       value: stats.approved, color: 'green',  icon: '✅' },
                    { label: 'Rejected',       value: stats.rejected, color: 'red',    icon: '❌' },
                ]" :key="i"
                    class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex items-center gap-4">
                    <span class="text-3xl">{{ item.icon }}</span>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ item.label }}</p>
                        <p class="text-3xl font-black text-gray-900">{{ item.value }}</p>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-gray-900">Leave Requests</h3>
                        <p class="text-sm text-gray-400">Manage and review all leave applications</p>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input v-model="search" type="text" placeholder="Search employee..." class="pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none w-48" />
                        </div>
                        <!-- Status Filter -->
                        <select v-model="filterStatus" class="border border-gray-200 rounded-xl text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <!-- Download (HR/Admin) -->
                        <a v-if="$page.props.auth.user.permissions.includes('download reports') || $page.props.auth.user.roles.includes('Super Admin')"
                           :href="route('leaves.download')"
                           class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl px-4 py-2 text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download CSV
                        </a>
                        <!-- Apply Leave -->
                        <button @click="showModal = true" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl px-4 py-2 text-sm transition-colors shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Apply Leave
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Leave Type</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Days</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reviewed By</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img class="w-8 h-8 rounded-full border border-gray-200" :src="'https://ui-avatars.com/api/?name='+leave.user.name+'&background=random'" alt="" />
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">{{ leave.user.name }}</p>
                                            <p class="text-xs text-gray-400">{{ leave.user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm text-gray-700 font-medium capitalize">{{ leave.type }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700">
                                        <span class="font-semibold">{{ leave.from_date }}</span>
                                        <span class="text-gray-400 mx-1">→</span>
                                        <span class="font-semibold">{{ leave.to_date }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ leave.reason }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-bold text-gray-900">{{ leave.days }}d</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full uppercase tracking-wide" :class="statusClass(leave.status)">
                                        {{ leave.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="leave.reviewer" class="text-sm">
                                        <p class="font-semibold text-gray-700">{{ leave.reviewer.name }}</p>
                                        <p v-if="leave.review_note" class="text-xs text-gray-400 line-clamp-1">{{ leave.review_note }}</p>
                                    </div>
                                    <span v-else class="text-gray-300 text-sm">—</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="leave.status === 'pending' && ($page.props.auth.user.permissions.includes('approve leaves') || $page.props.auth.user.roles.includes('Super Admin'))"
                                         class="flex items-center gap-2">
                                        <button @click="openReview(leave, 'approved')"
                                            class="inline-flex items-center gap-1 bg-green-100 text-green-700 hover:bg-green-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Approve
                                        </button>
                                        <button @click="openReview(leave, 'rejected')"
                                            class="inline-flex items-center gap-1 bg-red-100 text-red-700 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Reject
                                        </button>
                                    </div>
                                    <span v-else-if="leave.status !== 'pending'" class="text-xs text-gray-400 italic">Reviewed</span>
                                    <span v-else class="text-xs text-amber-500 font-semibold">Awaiting review</span>
                                </td>
                            </tr>
                            <tr v-if="leaves.data.length === 0">
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <span class="text-5xl">🗓️</span>
                                        <p class="text-gray-500 font-medium">No leave requests found</p>
                                        <button @click="showModal = true" class="text-indigo-600 text-sm font-semibold hover:underline">Apply for leave →</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-gray-100">
                    <Pagination :links="leaves.links" />
                </div>
            </div>
        </div>

        <!-- Apply Leave Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
                        <div class="flex items-center justify-between p-6 border-b border-gray-100">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Apply for Leave</h3>
                                <p class="text-sm text-gray-400 mt-0.5">Submit your leave request</p>
                            </div>
                            <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form @submit.prevent="submitLeave" class="p-6 space-y-4">
                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Leave Type</label>
                                <select v-model="form.type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                                    <option v-for="t in leaveTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                                <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                            </div>
                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">From Date</label>
                                    <input v-model="form.from_date" type="date" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                                    <p v-if="form.errors.from_date" class="text-red-500 text-xs mt-1">{{ form.errors.from_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">To Date</label>
                                    <input v-model="form.to_date" type="date" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                                    <p v-if="form.errors.to_date" class="text-red-500 text-xs mt-1">{{ form.errors.to_date }}</p>
                                </div>
                            </div>
                            <!-- Reason -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Reason</label>
                                <textarea v-model="form.reason" rows="3" placeholder="Please describe your reason..." class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none" required></textarea>
                                <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showModal = false" class="flex-1 border border-gray-200 text-gray-700 font-semibold rounded-xl py-2.5 hover:bg-gray-50 transition-colors text-sm">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="form.processing" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl py-2.5 transition-colors shadow-md text-sm disabled:opacity-50">
                                    {{ form.processing ? 'Submitting...' : '🚀 Submit Request' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Review Modal (Approve/Reject) -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="reviewModal = null"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                        <div class="p-6 border-b" :class="reviewModal.action === 'approved' ? 'border-green-100 bg-green-50' : 'border-red-100 bg-red-50'">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                     :class="reviewModal.action === 'approved' ? 'bg-green-100' : 'bg-red-100'">
                                    <span class="text-xl">{{ reviewModal.action === 'approved' ? '✅' : '❌' }}</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 capitalize">{{ reviewModal.action }} Leave</h3>
                                    <p class="text-sm text-gray-500">{{ reviewModal.leave.user.name }} — {{ reviewModal.leave.type }}</p>
                                </div>
                            </div>
                        </div>
                        <form @submit.prevent="submitReview" class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Note (Optional)</label>
                                <textarea v-model="reviewForm.review_note" rows="3" :placeholder="reviewModal.action === 'approved' ? 'e.g. Approved. Please ensure handover is done.' : 'e.g. Please reschedule due to project deadline.'"
                                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none"></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="reviewModal = null" class="flex-1 border border-gray-200 text-gray-700 font-semibold rounded-xl py-2.5 hover:bg-gray-50 transition-colors text-sm">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="reviewForm.processing"
                                        class="flex-1 font-bold rounded-xl py-2.5 transition-colors shadow-md text-sm text-white disabled:opacity-50"
                                        :class="reviewModal.action === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'">
                                    {{ reviewForm.processing ? 'Processing...' : (reviewModal.action === 'approved' ? '✅ Confirm Approve' : '❌ Confirm Reject') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>
