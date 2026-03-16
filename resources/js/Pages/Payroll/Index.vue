<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    payrolls: Object,
    employees: Array,
    canManage: Boolean,
    settings: Object,
    stats: Object,
});

// ─── Modals ─────────────────────────────────────────────
const showAutoModal   = ref(false);
const showManualModal = ref(false);
const showSlipModal   = ref(false);
const selectedPayroll = ref(null);

// ─── Auto-generate Form ──────────────────────────────────
const autoForm = useForm({
    user_id: '',
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    bonuses: 0,
    extra_deductions: 0,
});

const submitAuto = () => {
    autoForm.post(route('payroll.auto-generate'), {
        onSuccess: () => { showAutoModal.value = false; autoForm.reset(); }
    });
};

// Selected employee for preview
const selectedEmployee = computed(() =>
    props.employees.find(e => e.id == autoForm.user_id)
);

// ─── Manual Form ─────────────────────────────────────────
const manualForm = useForm({
    user_id: '',
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    base_salary: '',
    deductions: 0,
    bonuses: 0,
});

const submitManual = () => {
    manualForm.post(route('payroll.store'), {
        onSuccess: () => { showManualModal.value = false; manualForm.reset(); }
    });
};

// ─── Helpers ─────────────────────────────────────────────
const months = [
    { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
    { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
    { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
    { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
];
const getMonthName = (v) => months.find(m => m.value == v)?.label || v;

const formatCurrency = (n) =>
    new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(n ?? 0);

const numberToWords = (amount) => {
    if (!amount) return 'Zero';
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
        'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const convert = (n) => {
        if (n < 20) return ones[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 !== 0 ? ' ' + ones[n % 10] : '');
        if (n < 1000) return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 !== 0 ? ' ' + convert(n % 100) : '');
        if (n < 100000) return convert(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 !== 0 ? ' ' + convert(n % 1000) : '');
        if (n < 10000000) return convert(Math.floor(n / 100000)) + ' Lakh' + (n % 100000 !== 0 ? ' ' + convert(n % 100000) : '');
        return convert(Math.floor(n / 10000000)) + ' Crore' + (n % 10000000 !== 0 ? ' ' + convert(n % 10000000) : '');
    };
    const intPart = Math.floor(amount);
    return 'Indian Rupee ' + convert(intPart) + ' Only';
};

const updateStatus = (id, status) => {
    router.put(route('payroll.status', id), { status }, { preserveScroll: true });
};

const deletePayroll = (id) => {
    if (confirm('Are you sure you want to remove this payroll record?')) {
        router.delete(route('payroll.destroy', id), { preserveScroll: true });
    }
};

const viewSlip = (payroll) => {
    selectedPayroll.value = payroll;
    showSlipModal.value = true;
};

const printSlip = () => window.print();

const manualNetPreview = computed(() => {
    const base = parseFloat(manualForm.base_salary) || 0;
    const bonus = parseFloat(manualForm.bonuses) || 0;
    const ded = parseFloat(manualForm.deductions) || 0;
    return base + bonus - ded;
});

const companyName = computed(() => props.settings?.company_name || 'Your Company');
const companyLocation = computed(() => props.settings?.company_location || '');
</script>

<template>
    <Head title="Payroll Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6">
                <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                    Payroll Management
                </h2>
                <div class="flex items-center gap-3 ml-auto" v-if="canManage">
                    <button @click="showAutoModal = true"
                        class="bg-[#2CA01C] hover:bg-[#238016] text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap active:scale-95">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Auto Generate
                    </button>
                    <button @click="showManualModal = true"
                        class="bg-indigo-600 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap active:scale-95">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Manual Entry
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 px-1">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Stats Row -->
                <div v-if="canManage && stats" class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(item, i) in [
                        { label: 'Total Records', value: stats.total, color: 'indigo', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
                        { label: 'Paid', value: stats.paid, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: 'Pending', value: stats.pending, color: 'amber', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: 'Total Disbursed', value: stats.total_amount, color: 'rose', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', isCurrency: true },
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
                            <p class="text-2xl font-black text-gray-900 mt-1">{{ item.isCurrency ? formatCurrency(item.value ?? 0) : (item.value ?? 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    :headers="[
                        { key: 'employee', label: 'Employee' },
                        { key: 'period', label: 'Pay Period' },
                        { key: 'attendance', label: 'Attendance' },
                        { key: 'salary', label: 'Salary Breakdown' },
                        { key: 'net', label: 'Net Salary' },
                        { key: 'status', label: 'Status' },
                        { key: 'actions', label: 'Actions' }
                    ]"
                    :items="payrolls.data"
                    :searchable="false"
                >
                    <template #row="{ item: payroll }">
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-4">
                                <img class="h-11 w-11 rounded-[1.2rem] border-2 border-white shadow-md ring-1 ring-gray-100"
                                    :src="'https://ui-avatars.com/api/?name='+(payroll.user?.name || 'U')+'&background=4f46e5&color=fff'" alt="" />
                                <div>
                                    <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ payroll.user?.name }}</p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ payroll.user?.designation || payroll.user?.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <span class="inline-flex px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] border border-indigo-100">
                                {{ getMonthName(payroll.month) }} {{ payroll.year }}
                            </span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div v-if="payroll.working_days > 0" class="space-y-0.5">
                                <div class="text-[10px] font-black text-gray-700">{{ payroll.present_days }}<span class="text-gray-400 font-bold"> / {{ payroll.working_days }} Days</span></div>
                                <div class="text-[9px] font-black text-rose-500 uppercase tracking-widest">LOP: {{ payroll.lop_days }} Days</div>
                            </div>
                            <span v-else class="text-[9px] text-gray-300 italic font-bold">Manual</span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="space-y-0.5 text-[10px] font-bold">
                                <div><span class="text-gray-400">Base:</span> <span class="text-gray-800 font-black">{{ formatCurrency(payroll.base_salary) }}</span></div>
                                <div><span class="text-emerald-500">+Bonus:</span> <span class="text-emerald-700 font-black">{{ formatCurrency(payroll.bonuses) }}</span></div>
                                <div><span class="text-rose-500">-Deductions:</span> <span class="text-rose-700 font-black">{{ formatCurrency(payroll.deductions) }}</span></div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <span class="text-xl font-black text-gray-900">{{ formatCurrency(payroll.net_salary) }}</span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <select v-if="canManage" v-model="payroll.status" @change="updateStatus(payroll.id, $event.target.value)"
                                class="text-[9px] font-black uppercase tracking-[0.2em] border-2 rounded-2xl px-4 py-2 transition-all cursor-pointer border-transparent focus:ring-4"
                                :class="payroll.status === 'paid' ? 'bg-emerald-50 text-emerald-700 focus:ring-emerald-50' : 'bg-amber-50 text-amber-700 focus:ring-amber-50'">
                                <option value="pending">⏳ Pending</option>
                                <option value="paid">✅ Paid</option>
                            </select>
                            <span v-else class="px-4 py-2 rounded-2xl text-[9px] font-black uppercase tracking-widest"
                                :class="payroll.status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                {{ payroll.status === 'paid' ? '✅ Paid' : '⏳ Pending' }}
                            </span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="viewSlip(payroll)"
                                    class="bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all border border-indigo-100 active:scale-90">
                                    Salary Slip
                                </button>
                                <button v-if="canManage" @click="deletePayroll(payroll.id)"
                                    class="bg-rose-50 hover:bg-rose-600 hover:text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all border border-rose-100 text-rose-700 active:scale-90">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </template>
                </DataTable>

                <div class="flex justify-end pr-4 mt-4">
                    <Pagination :links="payrolls.links" />
                </div>
            </div>
        </div>

        <!-- ───────── AUTO GENERATE MODAL ───────── -->
        <Modal :show="showAutoModal" @close="showAutoModal = false" title="Auto Generate Payroll" maxWidth="xl">
            <form @submit.prevent="submitAuto" class="space-y-6">
                <div class="bg-indigo-50 rounded-2xl p-4 border border-indigo-100">
                    <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">🤖 Smart Calculation</p>
                    <p class="text-xs text-indigo-500 mt-1">Automatically calculates based on attendance, LOP, PF (12%), and Professional Tax.</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Select Employee</label>
                    <select v-model="autoForm.user_id" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                        <option value="">Choose Employee</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                            {{ emp.name }} {{ emp.base_salary ? '— ₹' + Number(emp.base_salary).toLocaleString('en-IN') + '/mo' : '(No salary set)' }}
                        </option>
                    </select>
                    <p v-if="selectedEmployee && !selectedEmployee.base_salary" class="text-amber-600 text-[10px] font-black mt-2 ml-1">⚠️ This employee has no base salary set. Update it in User Management first.</p>
                    <p v-if="autoForm.errors.user_id" class="text-rose-500 text-[10px] font-black mt-2 ml-1">{{ autoForm.errors.user_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Month</label>
                        <select v-model="autoForm.month" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Year</label>
                        <input v-model="autoForm.year" type="number" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" min="2020" max="2030" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Bonus (₹)</label>
                        <input v-model="autoForm.bonuses" type="number" step="0.01" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" min="0" placeholder="0" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Extra Deductions (₹)</label>
                        <input v-model="autoForm.extra_deductions" type="number" step="0.01" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" min="0" placeholder="0" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" @click="showAutoModal = false" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="autoForm.processing"
                        class="px-12 py-3.5 bg-[#2CA01C] hover:bg-[#238016] text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-green-100 active:scale-95 disabled:opacity-50">
                        {{ autoForm.processing ? 'Calculating...' : '🤖 Auto Generate' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- ───────── MANUAL ENTRY MODAL ───────── -->
        <Modal :show="showManualModal" @close="showManualModal = false" title="Manual Payroll Entry" maxWidth="xl">
            <form @submit.prevent="submitManual" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Select Employee</label>
                    <select v-model="manualForm.user_id" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required>
                        <option value="">Choose Employee</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Month</label>
                        <select v-model="manualForm.month" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Year</label>
                        <input v-model="manualForm.year" type="number" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" min="2020" max="2030" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Base Salary (₹)</label>
                    <input v-model="manualForm.base_salary" type="number" step="0.01" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" required min="0" placeholder="e.g. 25000" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Bonuses (₹)</label>
                        <input v-model="manualForm.bonuses" type="number" step="0.01" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" min="0" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Deductions (₹)</label>
                        <input v-model="manualForm.deductions" type="number" step="0.01" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5" min="0" />
                    </div>
                </div>

                <div class="bg-indigo-50 rounded-2xl p-5 border border-indigo-100">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-1">Net Salary Preview</p>
                    <p class="text-3xl font-black text-indigo-700">{{ formatCurrency(manualNetPreview) }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" @click="showManualModal = false" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="manualForm.processing"
                        class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95 disabled:opacity-50">
                        {{ manualForm.processing ? 'Saving...' : 'Save Payroll' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- ───────── SALARY SLIP MODAL (ZOHO STYLE) ───────── -->
        <Modal :show="showSlipModal" @close="showSlipModal = false" title="Salary Slip" maxWidth="3xl">
            <div v-if="selectedPayroll" id="salary-slip" class="font-sans text-gray-800">

                <!-- Company Header -->
                <div class="flex items-start justify-between pb-5 mb-5 border-b-2 border-gray-200">
                    <div class="flex items-center gap-4">
                        <!-- Company Logo / Initials -->
                        <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-black text-xl">{{ companyName.charAt(0) }}</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-gray-900 leading-tight">{{ companyName }}</h1>
                            <p v-if="companyLocation" class="text-xs text-gray-500 font-semibold">{{ companyLocation }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 font-semibold">Payslip For the Month</p>
                        <p class="text-lg font-black text-gray-900">{{ getMonthName(selectedPayroll.month) }} {{ selectedPayroll.year }}</p>
                    </div>
                </div>

                <!-- Employee Summary -->
                <div class="flex gap-6 mb-6">
                    <!-- Employee Details Table -->
                    <div class="flex-1">
                        <h3 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-3">Employee Summary</h3>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr>
                                    <td class="py-1.5 text-gray-500 font-semibold w-[40%]">Employee Name</td>
                                    <td class="py-1.5 font-bold">: &nbsp;{{ selectedPayroll.user?.name }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500 font-semibold">Designation</td>
                                    <td class="py-1.5 font-bold">: &nbsp;{{ selectedPayroll.user?.designation || '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500 font-semibold">Employee ID</td>
                                    <td class="py-1.5 font-bold">: &nbsp;{{ selectedPayroll.user?.employee_id || String(selectedPayroll.user_id).padStart(5, '0') }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500 font-semibold">Date of Joining</td>
                                    <td class="py-1.5 font-bold">: &nbsp;{{ selectedPayroll.user?.joining_date || '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500 font-semibold">Pay Period</td>
                                    <td class="py-1.5 font-bold">: &nbsp;{{ getMonthName(selectedPayroll.month) }} {{ selectedPayroll.year }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Net Pay Box -->
                    <div class="w-52 bg-emerald-50 border border-emerald-200 rounded-2xl p-5 text-center flex flex-col justify-center">
                        <p class="text-2xl font-black text-emerald-700 leading-tight">{{ formatCurrency(selectedPayroll.net_salary) }}</p>
                        <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest mt-1">Employee Net Pay</p>
                        <div class="mt-3 text-left space-y-1 border-t border-emerald-200 pt-3">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 font-semibold">Paid Days</span>
                                <span class="font-black">{{ selectedPayroll.present_days || '—' }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 font-semibold">LOP Days</span>
                                <span class="font-black text-rose-600">{{ selectedPayroll.lop_days || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings & Deductions Table -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <!-- Earnings -->
                    <div>
                        <table class="w-full text-sm border border-gray-200 rounded-xl overflow-hidden">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Earnings</th>
                                    <th class="px-4 py-3 text-right text-xs font-black text-gray-700 uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="px-4 py-2.5 text-sm text-gray-700">Basic Salary</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm">{{ formatCurrency(selectedPayroll.base_salary) }}</td>
                                </tr>
                                <tr v-if="selectedPayroll.bonuses > 0">
                                    <td class="px-4 py-2.5 text-sm text-gray-700">Performance Bonus</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm text-emerald-700">{{ formatCurrency(selectedPayroll.bonuses) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td class="px-4 py-3 font-black text-sm text-gray-900">Gross Earnings</td>
                                    <td class="px-4 py-3 text-right font-black text-sm">{{ formatCurrency(Number(selectedPayroll.base_salary) + Number(selectedPayroll.bonuses ?? 0)) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <table class="w-full text-sm border border-gray-200 rounded-xl overflow-hidden">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Deductions</th>
                                    <th class="px-4 py-3 text-right text-xs font-black text-gray-700 uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="selectedPayroll.pf_contribution > 0">
                                    <td class="px-4 py-2.5 text-sm text-gray-700">EPF Contribution (12%)</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm text-rose-700">{{ formatCurrency(selectedPayroll.pf_contribution) }}</td>
                                </tr>
                                <tr v-if="selectedPayroll.professional_tax > 0">
                                    <td class="px-4 py-2.5 text-sm text-gray-700">Professional Tax</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm text-rose-700">{{ formatCurrency(selectedPayroll.professional_tax) }}</td>
                                </tr>
                                <tr v-if="selectedPayroll.lop_deduction > 0">
                                    <td class="px-4 py-2.5 text-sm text-gray-700">LOP Deduction ({{ selectedPayroll.lop_days }} days)</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm text-rose-700">{{ formatCurrency(selectedPayroll.lop_deduction) }}</td>
                                </tr>
                                <tr v-if="selectedPayroll.extra_deductions > 0">
                                    <td class="px-4 py-2.5 text-sm text-gray-700">Other Deductions</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm text-rose-700">{{ formatCurrency(selectedPayroll.extra_deductions) }}</td>
                                </tr>
                                <tr v-if="!selectedPayroll.pf_contribution && !selectedPayroll.professional_tax && !selectedPayroll.lop_deduction">
                                    <td class="px-4 py-2.5 text-sm text-gray-400 italic">Total Deductions</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm">{{ formatCurrency(selectedPayroll.deductions) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td class="px-4 py-3 font-black text-sm text-gray-900">Total Deductions</td>
                                    <td class="px-4 py-3 text-right font-black text-sm text-rose-700">{{ formatCurrency(selectedPayroll.deductions) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Net Payable Banner -->
                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-2xl px-6 py-4 mb-4">
                    <div>
                        <p class="font-black text-gray-900 text-sm uppercase tracking-widest">Total Net Payable</p>
                        <p class="text-xs text-gray-500 mt-0.5">Gross Earnings - Total Deductions</p>
                    </div>
                    <p class="text-2xl font-black text-emerald-700">{{ formatCurrency(selectedPayroll.net_salary) }}</p>
                </div>

                <!-- Amount in Words -->
                <div class="text-center bg-gray-50 rounded-xl py-3 px-4 mb-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold">Amount in Words : <span class="text-gray-700 font-black">{{ numberToWords(selectedPayroll.net_salary) }}</span></p>
                </div>

                <!-- Status -->
                <div class="text-center mb-4">
                    <span class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-sm font-black uppercase tracking-widest"
                        :class="selectedPayroll.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                        {{ selectedPayroll.status === 'paid' ? '✅ Salary Disbursed' : '⏳ Payment Pending' }}
                    </span>
                </div>

                <!-- Footer -->
                <p class="text-center text-[10px] text-gray-400 italic border-t pt-3">
                    — This document has been automatically generated by {{ companyName }} Payroll System; therefore, a signature is not required. —
                </p>
            </div>

            <template #footer>
                <button type="button" @click="showSlipModal = false"
                    class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">
                    Close
                </button>
                <button @click="printSlip"
                    class="bg-[#2CA01C] hover:bg-[#238016] text-white px-8 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] flex items-center gap-2 shadow-lg transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Print / Save PDF
                </button>
            </template>
        </Modal>

    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #salary-slip, #salary-slip * { visibility: visible; }
    #salary-slip { position: fixed; top: 0; left: 0; width: 100%; }
}
</style>
