<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    attendances: Object,
    todayAttendance: Object,
    isTodayHoliday: Boolean,
    filters: Object,
    officeLocation: Object,
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

onMounted(async () => {
    try {
        const pos = await getPosition();
        const el = document.getElementById('current-coords');
        if (el) el.innerText = `Current: ${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
    } catch (e) {
        const el = document.getElementById('current-coords');
        if (el) el.innerText = 'Location access blocked';
    }
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
</script>

<template>
    <Head title="Attendance System" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Staff Attendance Directory
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Geofence Status Header -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📍</span>
                        <div class="text-sm">
                            <p class="font-bold text-indigo-900 uppercase tracking-tight text-xs">Geofencing Active</p>
                            <p class="text-indigo-600 font-medium tracking-tight">Office Radius: 200m</p>
                        </div>
                    </div>
                    <div class="text-[10px] font-black text-gray-400 uppercase text-right leading-tight">
                        HQ: {{ officeLocation.lat }}, {{ officeLocation.lng }}
                        <div id="current-coords" class="text-indigo-500 mt-1 lowercase">detecting your location...</div>
                    </div>
                </div>

                <!-- Holiday Notice -->
                <div v-if="isTodayHoliday && (!todayAttendance || !todayAttendance.clock_in)" 
                     class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-4 text-amber-800 shadow-sm animate-pulse-slow">
                    <span class="text-2xl">🎉</span>
                    <div>
                        <p class="font-bold">Today is a Holiday / Non-working Day!</p>
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
                            <h3 class="text-lg font-black text-gray-900 leading-tight">Attendance Checkpoint</h3>
                            <p class="text-xs text-gray-400 font-medium">Record shift securely. Protected by IP & Geofencing.</p>
                            <div v-if="$page.props.auth.user.allowed_ip" class="mt-1.5 flex items-center gap-1.5">
                                <span class="bg-[#2CA01C]/10 text-[#2CA01C] text-[10px] font-bold px-2 py-0.5 rounded border border-[#2CA01C]/20 uppercase tracking-tighter shadow-sm">
                                    Trusted IP: {{ $page.props.auth.user.allowed_ip }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <button v-if="!todayAttendance || !todayAttendance.clock_in" 
                                @click="clockIn" 
                                :disabled="isLocating"
                                class="bg-[#2CA01C] hover:bg-[#238016] text-white px-8 py-3.5 rounded-2xl font-black shadow-lg hover:shadow-xl transition-all flex items-center gap-3 active:scale-95 group disabled:opacity-50">
                            <svg v-if="isLocating" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-6 h-6 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            {{ isLocating ? 'Verifying...' : 'Clock In' }}
                        </button>
                        
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

                <!-- Table Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    
                    <!-- Toolbar -->
                    <div class="p-6 bg-white border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-gray-900 font-black text-lg">Detailed Logs</div>
                        <div v-if="$page.props.auth.user.permissions.includes('manage attendance') || $page.props.auth.user.roles.includes('Super Admin')" class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Search employee..." class="block w-full pl-9 pr-3 py-2 border border-gray-100 bg-gray-50 rounded-xl focus:ring-[#2CA01C] focus:border-[#2CA01C] text-sm shadow-inner transition-colors text-gray-700" />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr class="bg-gray-50">
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Employee</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">In (Loc)</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Out (Loc)</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-50">
                                <tr v-for="log in attendances.data" :key="log.id" class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img class="h-8 w-8 rounded-full border border-gray-100 shadow-sm" :src="'https://ui-avatars.com/api/?name='+log.user.name+'&background=random'" alt="Avatar">
                                            <div class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ log.user.name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs font-bold text-gray-600 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ log.date }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div v-if="log.clock_in" class="flex flex-col">
                                            <span class="font-black text-emerald-600 tracking-tighter">{{ log.clock_in }}</span>
                                            <span v-if="log.lat" class="text-[8px] text-gray-400 font-black tracking-widest uppercase opacity-60">
                                                {{ log.lat }}, {{ log.lng }}
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-300 italic font-black text-[10px]">--:--</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div v-if="log.clock_out" class="flex flex-col">
                                            <span class="font-black text-rose-600 tracking-tighter">{{ log.clock_out }}</span>
                                            <span v-if="log.out_lat" class="text-[8px] text-gray-400 font-black tracking-widest uppercase opacity-60">
                                                {{ log.out_lat }}, {{ log.out_lng }}
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-300 italic font-black text-[10px]">--:--</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-[10px] leading-5 font-black rounded-full uppercase tracking-widest shadow-sm border" :class="{
                                            'bg-green-50 text-green-700 border-green-100': log.status === 'present',
                                            'bg-red-50 text-red-700 border-red-100': log.status === 'absent',
                                            'bg-amber-50 text-amber-700 border-amber-100': log.status === 'half-day'
                                        }">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="attendances.data.length === 0">
                                    <td colspan="5" class="px-6 py-16 text-center text-gray-400 font-black uppercase text-sm tracking-widest">No logs found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="attendances.links" class="mt-6" />
            </div>
        </div>

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
