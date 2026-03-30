<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    attendances: Object,
    todayAttendance: Object,
    isTodayHoliday: Boolean,
    filters: Object,
    officeLocation: Object,
});

const page = usePage();
const isSuperAdmin = computed(() => {
    return page.props.auth.user.roles.some(r => r.toLowerCase().includes('super admin')) || 
           page.props.auth.user.roles.some(r => r.toLowerCase() === 'admin');
});

const search = ref(props.filters?.search || '');
const isLocating = ref(false);

watch(search, debounce(function (value) {
    router.get(route('attendance.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const getPosition = () => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error("Geolocation is not supported by your browser."));
        }
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    });
};

const fetchCurrentPosition = async () => {
    try {
        isLocating.value = true;
        const pos = await getPosition();
        const el = document.getElementById('current-coords');
        if (el) el.innerText = `Current: ${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
        isLocating.value = false;
    } catch (e) {
        isLocating.value = false;
        const el = document.getElementById('current-coords');
        if (el) el.innerText = 'Location access blocked';
    }
};

onMounted(() => {
    fetchCurrentPosition();
});

const clockIn = async () => {
    isLocating.value = true;
    try {
        const position = await getPosition();
        router.post(route('attendance.clock-in'), {
            lat: position.coords.latitude,
            lng: position.coords.longitude
        }, { 
            preserveScroll: true,
            onFinish: () => isLocating.value = false
        });
    } catch (error) {
        isLocating.value = false;
        alert("❌ Error: " + error.message + ". Please enable GPS/Location access.");
    }
};

// Work From Home
const showWfhModal = ref(false);
const wfhForm = useForm({
    is_wfh: true,
    wfh_reason: ''
});

const openWfhModal = () => {
    wfhForm.reset();
    showWfhModal.value = true;
};

const submitWfh = () => {
    wfhForm.post(route('attendance.clock-in'), {
        preserveScroll: true,
        onSuccess: () => {
            showWfhModal.value = false;
        }
    });
};

const clockOut = async () => {
    isLocating.value = true;
    try {
        const position = await getPosition();
        router.post(route('attendance.clock-out'), {
            lat: position.coords.latitude,
            lng: position.coords.longitude
        }, { 
            preserveScroll: true,
            onFinish: () => isLocating.value = false
        });
    } catch (error) {
        isLocating.value = false;
        alert("❌ Error: " + error.message + ". Please enable GPS/Location access.");
    }
};

// Edit Attendance
const showEditModal = ref(false);
const selectedAttendance = ref(null);
const editForm = useForm({
    clock_in: '',
    clock_out: '',
    status: 'present',
    date: ''
});

const openEditModal = (attendance) => {
    selectedAttendance.value = attendance;
    editForm.clock_in = attendance.clock_in || '';
    editForm.clock_out = attendance.clock_out || '';
    editForm.status = attendance.status;
    editForm.date = attendance.date;
    showEditModal.value = true;
};

// Import Attendance
const importForm = useForm({
    file: null
});

const uploadFile = (event) => {
    importForm.file = event.target.files[0];
    if (importForm.file) {
        importForm.post(route('attendance.import'), {
            preserveScroll: true,
            onSuccess: () => {
                importForm.reset();
            }
        });
    }
};

const triggerFileInput = () => {
    document.getElementById('csv-import-input').click();
};

const exportAttendance = () => {
    window.location.href = route('attendance.export');
};

const updateAttendance = () => {
    editForm.put(route('attendance.update', selectedAttendance.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};

const deleteAttendance = (attendanceId) => {
    if (confirm('Are you sure you want to delete this record?')) {
        router.delete(route('attendance.destroy', attendanceId));
    }
};
</script>

<template>
    <Head title="Attendance System" />

    <AuthenticatedLayout>
        <template #header>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                        Attendance
                    </h2>
                    
                    <div v-if="isSuperAdmin" class="flex items-center gap-2 ml-4">
                        <Link :href="route('attendance.calendar')" class="bg-white text-indigo-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-indigo-100 hover:bg-indigo-50 transition-all active:scale-95 shadow-sm flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Calendar View
                        </Link>
                        <button @click="exportAttendance" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all active:scale-95 shadow-sm">
                            Export CSV
                        </button>
                        <button @click="triggerFileInput" class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-emerald-100 hover:bg-emerald-600 hover:text-white transition-all active:scale-95 shadow-sm">
                            Import CSV
                        </button>
                        <input id="csv-import-input" type="file" class="hidden" accept=".csv" @change="uploadFile" />
                    </div>
                </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Geofence Status Header -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📍</span>
                        <div class="text-sm">
                            <p class="font-bold text-indigo-900 uppercase tracking-tight text-xs">Tracking</p>
                            <p class="text-indigo-600 font-medium tracking-tight">Radius: {{ officeLocation.radius }}m</p>
                        </div>
                    </div>
                    <div class="text-[10px] font-black text-gray-400 uppercase text-right leading-tight">
                        HQ: {{ officeLocation.lat }}, {{ officeLocation.lng }}
                        <div id="current-coords" class="text-indigo-500 mt-1 lowercase">checking your location...</div>
                        <button v-if="!isLocating" @click="fetchCurrentPosition" class="mt-1 text-indigo-400 hover:text-indigo-600 underline text-[9px] font-black uppercase tracking-widest">
                            Update Location
                        </button>
                    </div>
                </div>

                <!-- Holiday Notice -->
                <div v-if="isTodayHoliday && (!todayAttendance || !todayAttendance.clock_in)" 
                     class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-4 text-amber-800 shadow-sm animate-pulse-slow">
                    <span class="text-2xl">🎉</span>
                    <div>
                        <p class="font-bold">Today is a Holiday!</p>
                        <p class="text-xs opacity-80">Attendance is optional. Enjoy your day off!</p>
                    </div>
                </div>

                <!-- Quick Actions Dashboard -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-4 rounded-full bg-indigo-50 text-indigo-600 shadow-inner">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900 leading-tight">Mark Attendance</h3>
                            <p class="text-xs text-gray-400 font-medium">Click to clock in or out for today.</p>
                            <div v-if="$page.props.auth.user.allowed_ip" class="mt-1.5 flex items-center gap-1.5">
                                <span class="bg-indigo-50 text-indigo-600 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-100 uppercase tracking-tighter shadow-sm">
                                    Home IP: {{ $page.props.auth.user.allowed_ip }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div v-if="!todayAttendance || !todayAttendance.clock_in" class="flex flex-col sm:flex-row items-center gap-3">
                            <button @click="clockIn" 
                                    :disabled="isLocating"
                                    class="bg-indigo-600 hover:bg-gray-900 text-white px-8 py-3.5 rounded-2xl font-black shadow-lg hover:shadow-xl transition-all flex items-center gap-3 active:scale-95 group disabled:opacity-50">
                                <svg v-if="isLocating" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="w-6 h-6 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                {{ isLocating ? 'Verifying...' : 'Office In' }}
                            </button>
                            <button @click="openWfhModal" 
                                    class="bg-amber-100 hover:bg-amber-500 text-amber-700 hover:text-white px-6 py-3.5 rounded-2xl font-black shadow-sm transition-all flex items-center gap-2 active:scale-95 group">
                                <span class="text-xl">🏠</span>
                                WFH
                            </button>
                        </div>
                        
                        <div v-else-if="todayAttendance && !todayAttendance.clock_out" class="flex flex-col sm:flex-row items-center gap-4">
                            <div class="text-center sm:text-left">
                                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest leading-none mb-1">Shift Active</p>
                                <p class="text-xl font-black text-gray-900 leading-none">In at {{ todayAttendance.clock_in }}</p>
                            </div>
                            <button @click="clockOut" 
                                    :disabled="isLocating"
                                    class="h-14 bg-rose-600 hover:bg-rose-700 text-white px-8 rounded-2xl font-black shadow-lg hover:shadow-xl transition-all flex items-center gap-3 active:scale-95 group disabled:opacity-50">
                                <svg v-if="isLocating" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="w-6 h-6 transition-transform group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                {{ isLocating ? 'Verifying...' : 'Clock Out' }}
                            </button>
                        </div>

                        <div v-else class="text-center bg-gray-50 border border-gray-200 px-8 py-3.5 rounded-2xl shadow-inner flex flex-col items-center gap-0.5">
                            <span class="text-xl">✅</span>
                            <p class="text-xs font-black text-gray-900 uppercase">Shift Completed</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Attendance Logs -->
                <DataTable 
                    :headers="[
                        { key: 'user', label: 'Employee', sortable: true },
                        { key: 'date', label: 'Date', sortable: true },
                        { key: 'clock_in', label: 'Clock In' },
                        { key: 'clock_out', label: 'Clock Out' },
                        { key: 'status', label: 'Status' },
                        { key: 'actions', label: 'Actions' }
                    ]"
                    :items="attendances.data"
                    placeholder="Search logs..."
                    @search="val => search = val"
                >
                    <template #row="{ item: log }">
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 flex-shrink-0 relative">
                                    <img class="h-10 w-10 rounded-xl border border-gray-100 shadow-sm" :src="'https://ui-avatars.com/api/?name='+log.user.name+'&background=random'" alt="Avatar">
                                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <div class="w-2 h-2 rounded-full" :class="log.status === 'present' ? 'bg-emerald-500' : (log.status === 'absent' ? 'bg-rose-500' : 'bg-amber-400')"></div>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <div class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ log.user.name }}</div>
                                    <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ log.user.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <span class="text-xs font-black text-gray-700 uppercase tracking-widest underline decoration-indigo-100 decoration-2 underline-offset-4">{{ log.date }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div v-if="log.clock_in" class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-4 bg-emerald-500 rounded-full"></span>
                                    <span class="text-sm font-black text-gray-900 tracking-tighter">{{ log.clock_in }}</span>
                                </div>
                                <div v-if="log.lat" class="flex items-center gap-1.5 px-2 py-0.5 bg-gray-50 rounded-md border border-gray-100 w-max group cursor-help" :title="'Lat: ' + log.lat + ', Lng: ' + log.lng">
                                    <svg class="w-3 h-3 text-indigo-400 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="text-[9px] text-gray-400 font-black tracking-widest uppercase">Verified Geo</span>
                                </div>
                            </div>
                            <span v-else class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic opacity-50 flex items-center gap-2">
                                <span class="w-4 h-[1px] bg-gray-200"></span> Not Logged
                            </span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div v-if="log.clock_out" class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-4 bg-rose-500 rounded-full"></span>
                                    <span class="text-sm font-black text-gray-900 tracking-tighter">{{ log.clock_out }}</span>
                                </div>
                                <div v-if="log.out_lat" class="flex items-center gap-1.5 px-2 py-0.5 bg-gray-50 rounded-md border border-gray-100 w-max group cursor-help" :title="'Lat: ' + log.out_lat + ', Lng: ' + log.out_lng">
                                    <svg class="w-3 h-3 text-indigo-400 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="text-[9px] text-gray-400 font-black tracking-widest uppercase">Verified Geo</span>
                                </div>
                            </div>
                            <span v-else class="text-[10px] font-black text-gray-300 uppercase tracking-widest italic opacity-50 flex items-center gap-2">
                                <span class="w-4 h-[1px] bg-gray-200"></span> In Office
                            </span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <span class="px-5 py-2 inline-flex text-[9px] font-black rounded-2xl uppercase tracking-[0.2em] shadow-sm border transition-all" :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-100': log.status === 'present',
                                    'bg-rose-50 text-rose-700 border-rose-100': log.status === 'absent',
                                    'bg-amber-50 text-amber-700 border-amber-100': log.status === 'half_day' || log.status === 'half-day',
                                    'bg-orange-50 text-orange-700 border-orange-100': log.status === 'late'
                                }">
                                    {{ log.status }}
                                </span>
                                <button v-if="isSuperAdmin" @click="openEditModal(log)" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                <button v-if="isSuperAdmin" @click="deleteAttendance(log.id)" class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </template>
                </DataTable>

                <div class="flex justify-end pr-4 mt-8">
                    <Pagination :links="attendances.links" />
                </div>
            </div>
        </div>

        <!-- Edit Attendance Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" title="Edit Attendance Record" maxWidth="md">
            <form @submit.prevent="updateAttendance" class="space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Target Date</label>
                        <input v-model="editForm.date" type="date" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" required />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Clock In (HH:MM:SS)</label>
                            <input v-model="editForm.clock_in" type="text" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" placeholder="09:00:00" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Clock Out (HH:MM:SS)</label>
                            <input v-model="editForm.clock_out" type="text" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" placeholder="18:00:00" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Daily Status</label>
                        <select v-model="editForm.status" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner">
                            <option value="present">✅ Present</option>
                            <option value="absent">❌ Absent</option>
                            <option value="late">🕒 Late</option>
                            <option value="half_day">🌓 Half Day</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" @click="showEditModal = false" class="px-6 py-3 text-[10px] font-black uppercase text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="editForm.processing" class="px-8 py-3 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 disabled:opacity-50">
                        {{ editForm.processing ? 'Saving...' : 'Update Record' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- WFH Modal -->
        <Modal :show="showWfhModal" @close="showWfhModal = false" title="Request Work From Home" maxWidth="md">
            <form @submit.prevent="submitWfh" class="space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Reason / Details</label>
                        <textarea v-model="wfhForm.wfh_reason" rows="4" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm font-bold shadow-inner" placeholder="Why are you working from home today?" required></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" @click="showWfhModal = false" class="px-6 py-3 text-[10px] font-black uppercase text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="wfhForm.processing" class="px-8 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 disabled:opacity-50">
                        {{ wfhForm.processing ? 'Submitting...' : 'Confirm WFH' }}
                    </button>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
.animate-pulse-slow {
    animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
