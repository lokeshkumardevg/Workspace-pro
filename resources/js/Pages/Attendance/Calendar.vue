<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    attendanceData: Object,
    month: Number,
    year: Number,
    holidays: Array,
    employees: Array,
    selectedEmployeeId: Number
});

const page = usePage();
const isSuperAdmin = computed(() => {
    return page.props.auth.user.roles.some(r =>
        ['super admin', 'admin', 'hr', 'manager', 'team lead'].includes(r.toLowerCase())
    );
});

const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];

const daysInMonth  = computed(() => new Date(props.year, props.month, 0).getDate());
const startDay     = computed(() => new Date(props.year, props.month - 1, 1).getDay());

const calendarDays = computed(() => {
    const days = [];
    for (let i = 0; i < startDay.value; i++) days.push(null);
    for (let i = 1; i <= daysInMonth.value; i++) {
        const dateStr = `${props.year}-${String(props.month).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        const dow = new Date(props.year, props.month - 1, i).getDay();
        days.push({
            day: i,
            date: dateStr,
            attendance: props.attendanceData[dateStr] || null,
            isHoliday: props.holidays.includes(dateStr),
            isWeekend: dow === 0 || dow === 6,
        });
    }
    return days;
});

const changeMonth = (offset) => {
    let m = props.month + offset, y = props.year;
    if (m > 12) { m = 1; y++; }
    if (m < 1)  { m = 12; y--; }
    router.get(route('attendance.calendar'), { month: m, year: y, employee_id: props.selectedEmployeeId });
};

const handleEmployeeChange = (e) => {
    router.get(route('attendance.calendar'), {
        month: props.month,
        year: props.year,
        employee_id: e.target.value
    });
};

// Modal
const showModal      = ref(false);
const selectedDay    = ref(null);
const attendanceForm = useForm({ user_id: props.selectedEmployeeId, date: '', clock_in: '', clock_out: '', status: 'present' });

const openDayModal = (day) => {
    if (!isSuperAdmin.value) return;
    selectedDay.value = day;
    attendanceForm.date    = day.date;
    attendanceForm.user_id = props.selectedEmployeeId;
    if (day.attendance) {
        attendanceForm.clock_in  = day.attendance.clock_in  || '';
        attendanceForm.clock_out = day.attendance.clock_out || '';
        attendanceForm.status    = day.attendance.status;
    } else {
        attendanceForm.clock_in  = '09:00:00';
        attendanceForm.clock_out = '18:00:00';
        attendanceForm.status    = 'present';
    }
    showModal.value = true;
};
const saveAttendance = () => attendanceForm.post(route('attendance.store'), { onSuccess: () => showModal.value = false });
const deleteRecord   = (id) => confirm('Delete this record?') && router.delete(route('attendance.destroy', id));
</script>

<template>
    <Head title="Attendance Calendar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-black uppercase tracking-tighter text-gray-800">Attendance Calendar</h2>

                    <!-- Simple native select dropdown -->
                    <select
                        v-if="employees && employees.length > 0"
                        :value="selectedEmployeeId"
                        @change="handleEmployeeChange"
                        class="border border-gray-300 rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-colors cursor-pointer"
                    >
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                            {{ emp.name }}
                        </option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="changeMonth(-1)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="text-sm font-bold uppercase tracking-wider text-indigo-700 px-3">{{ monthNames[month-1] }} {{ year }}</span>
                    <button @click="changeMonth(1)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19l7-7-7-7"/></svg>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Calendar Grid -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                    <!-- Day headers -->
                    <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
                        <div v-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="d"
                             class="py-4 text-center text-[10px] font-black uppercase tracking-widest text-gray-400">
                            {{ d }}
                        </div>
                    </div>

                    <!-- Calendar cells -->
                    <div class="grid grid-cols-7">
                        <div
                            v-for="(day, idx) in calendarDays"
                            :key="idx"
                            @click="day && isSuperAdmin && openDayModal(day)"
                            class="min-h-[140px] border-b border-r border-gray-100 p-3 relative group transition-all"
                            :class="[
                                day && isSuperAdmin ? 'cursor-pointer hover:bg-indigo-50/30' : 'cursor-default',
                                day && day.isWeekend ? 'bg-slate-50/50' : '',
                            ]"
                        >
                            <template v-if="day">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-sm font-bold" :class="day.isWeekend ? 'text-rose-400' : 'text-gray-400'">{{ day.day }}</span>
                                    <span v-if="day.isHoliday && !day.isWeekend" class="text-[8px] font-black bg-amber-100 text-amber-700 border border-amber-200 px-2 py-0.5 rounded uppercase">Holiday</span>
                                    <span v-else-if="day.isWeekend" class="text-[8px] font-black bg-gray-100 text-gray-400 px-2 py-0.5 rounded uppercase">Off</span>
                                </div>

                                <!-- Attendance card -->
                                <div v-if="day.attendance" class="relative">
                                    <div class="rounded-xl px-3 py-2.5 shadow-md text-white" :class="{
                                        'bg-emerald-600': day.attendance.status === 'present',
                                        'bg-rose-600':    day.attendance.status === 'absent',
                                        'bg-amber-500':   day.attendance.status.includes('half'),
                                        'bg-orange-500':  day.attendance.status === 'late',
                                    }">
                                        <div class="text-[9px] font-black uppercase tracking-widest mb-1">{{ day.attendance.status }}</div>
                                        <div class="flex items-center gap-1 text-white/80 text-[9px]">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ day.attendance.clock_in }}
                                        </div>
                                    </div>
                                    <button
                                        v-if="isSuperAdmin"
                                        @click.stop="deleteRecord(day.attendance.id)"
                                        class="absolute -top-1.5 -right-1.5 p-1 bg-white text-rose-500 border border-gray-200 rounded-lg shadow opacity-0 group-hover:opacity-100 transition-opacity hover:bg-rose-600 hover:text-white"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <!-- Empty state - click to add -->
                                <div
                                    v-else-if="!day.isWeekend && !day.isHoliday && isSuperAdmin"
                                    class="mt-2 flex items-center justify-center py-5 border-2 border-dashed border-gray-100 rounded-xl text-gray-200 group-hover:border-indigo-200 group-hover:text-indigo-300 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-6 flex flex-wrap gap-6 items-center justify-center p-5 bg-white rounded-xl border border-gray-200 shadow-sm text-[11px] font-bold uppercase tracking-widest text-gray-400">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-emerald-600 rounded-full block"></span> Present</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-rose-600 rounded-full block"></span> Absent</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-amber-500 rounded-full block"></span> Half Day</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-orange-500 rounded-full block"></span> Late</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-gray-200 rounded-full block"></span> Weekend / Off</div>
                </div>
            </div>
        </div>

        <!-- Attendance Modal -->
        <Modal :show="showModal" @close="showModal = false" maxWidth="md">
            <div class="p-8">
                <h3 class="text-xl font-black uppercase tracking-tighter text-gray-900 mb-6 pb-4 border-b border-gray-100">
                    {{ selectedDay?.date }}
                </h3>
                <form @submit.prevent="saveAttendance" class="space-y-5">
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Clock In</label>
                            <input v-model="attendanceForm.clock_in" type="text" placeholder="09:00:00"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm font-bold text-center focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Clock Out</label>
                            <input v-model="attendanceForm.clock_out" type="text" placeholder="18:00:00"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm font-bold text-center focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Status</label>
                        <select v-model="attendanceForm.status"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="submit" :disabled="attendanceForm.processing"
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3.5 rounded-xl text-sm font-black uppercase tracking-widest transition-colors disabled:opacity-50">
                            {{ attendanceForm.processing ? 'Saving...' : 'Save' }}
                        </button>
                        <button type="button" @click="showModal = false"
                                class="px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-bold transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
