<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    report: Object,
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
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">
                Attendance Report
            </h2>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

                <!-- Filters -->
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-end justify-between gap-8">
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-1">Month</label>
                            <select v-model="month" class="w-48 bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner">
                                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-1">Year</label>
                            <select v-model="year" class="w-32 bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner">
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <button @click="applyFilter" class="bg-indigo-600 hover:bg-gray-900 text-white px-8 py-3 rounded-2xl font-black uppercase text-xs shadow-lg shadow-indigo-100 transition-all active:scale-95">
                            View Report
                        </button>
                    </div>

                    <a :href="route('attendance.export', { month, year })" class="flex items-center gap-2 bg-[#2CA01C] hover:bg-[#238016] text-white px-8 py-3 rounded-2xl font-black uppercase text-xs shadow-lg shadow-green-100 transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export CSV
                    </a>
                </div>

                <!-- Report Table using DataTable -->
                <DataTable 
                    :headers="[
                        { key: 'employee', label: 'Employee Name', sortable: true },
                        { key: 'working_days', label: 'Working Days' },
                        { key: 'present_days', label: 'Present' },
                        { key: 'absent_days', label: 'Absent' },
                        { key: 'percentage', label: 'Attendance %' },
                        { key: 'actions', label: 'Details' }
                    ]"
                    :items="report.data"
                    :searchable="false"
                >
                    <template #row="{ item }">
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-[1.2rem] bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-xs uppercase border border-indigo-100 shadow-sm">
                                    {{ item.employee.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-black text-gray-900 text-sm uppercase tracking-tight">{{ item.employee }}</p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ item.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 font-black text-gray-400 uppercase tracking-widest text-xs">{{ item.working_days }} Days</td>
                        <td class="px-6 py-6">
                            <span class="px-4 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 font-black text-[10px] uppercase shadow-sm border border-emerald-100 italic">
                                {{ item.present_days }} Days
                            </span>
                        </td>
                        <td class="px-6 py-6">
                            <span class="px-4 py-1.5 rounded-xl bg-rose-50 text-rose-700 font-black text-[10px] uppercase shadow-sm border border-rose-100 italic">
                                {{ item.absent_days }} Days
                            </span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                     <span class="text-[10px] font-black text-gray-900 uppercase tracking-tighter">{{ Math.round((item.present_days / item.working_days) * 100) }}% Attendance</span>
                                </div>
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden shadow-inner border border-gray-50">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-1000" :style="{ width: Math.round((item.present_days / item.working_days) * 100) + '%' }"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <button class="bg-gray-50 hover:bg-gray-900 hover:text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">Details</button>
                        </td>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div class="flex justify-end pr-4">
                    <Pagination :links="report.links" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

