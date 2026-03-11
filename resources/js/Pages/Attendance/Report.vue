<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    report: Array,
    filters: Object,
});

const month = ref(props.filters.month);
const year = ref(props.filters.year);

const months = [
    { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
    { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
    { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
    { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
];

const years = [2025, 2026, 2027];

const applyFilter = () => {
    router.get(route('attendance.report'), {
        month: month.value,
        year: year.value,
    }, { preserveState: true });
};
</script>

<template>
    <Head title="Attendance Monthly Report" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">
                Monthly Attendance Report
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Filters -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-wrap items-center gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Month</label>
                        <select v-model="month" class="rounded-xl border-gray-200 text-sm focus:ring-[#2CA01C] focus:border-[#2CA01C] bg-gray-50 pr-8">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Year</label>
                        <select v-model="year" class="rounded-xl border-gray-200 text-sm focus:ring-[#2CA01C] focus:border-[#2CA01C] bg-gray-50 pr-8">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <button @click="applyFilter" class="mt-5 bg-[#2CA01C] hover:bg-[#238016] text-white px-6 py-2 rounded-xl font-bold transition-all shadow-md">
                        Generate Report
                    </button>
                    <a :href="route('attendance.export', { month, year })" class="mt-5 bg-[#0077C5] hover:bg-[#005a96] text-white px-6 py-2 rounded-xl font-bold transition-all shadow-md">
                        Export CSV
                    </a>
                </div>

                <!-- Report Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Total Working Days</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Present Days</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Absent Days</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Attendance %</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(item, i) in report" :key="i" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[#2CA01C]/10 flex items-center justify-center text-[#2CA01C] font-bold text-xs uppercase">
                                            {{ item.employee.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm">{{ item.employee }}</p>
                                            <p class="text-xs text-gray-400">{{ item.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-gray-700 text-sm">{{ item.working_days }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 font-bold text-xs shadow-sm border border-green-100">
                                        {{ item.present_days }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full bg-red-50 text-red-700 font-bold text-xs shadow-sm border border-red-100">
                                        {{ item.absent_days }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-black text-gray-900">{{ Math.round((item.present_days / item.working_days) * 100) }}%</span>
                                        <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-[#2CA01C]" :style="{ width: Math.round((item.present_days / item.working_days) * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="text-indigo-600 hover:text-indigo-900 text-xs font-bold uppercase tracking-wider">Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
