<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
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
                <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                    Attendance
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
                        <button v-if="!isLocating" @click="fetchCurrentPosition" class="mt-1 text-indigo-400 hover:text-indigo-600 underline text-[9px] font-black uppercase tracking-widest">
                            Retry Location
                        </button>
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
                            <h3 class="text-lg font-black text-gray-900 leading-tight">Daily Presence</h3>
                            <p class="text-xs text-gray-400 font-medium">Record your daily shift. Protected by Geofencing.</p>
                            <div v-if="$page.props.auth.user.allowed_ip" class="mt-1.5 flex items-center gap-1.5">
                                <span class="bg-indigo-50 text-indigo-600 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-100 uppercase tracking-tighter shadow-sm">
                                    Home IP: {{ $page.props.auth.user.allowed_ip }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <button v-if="!todayAttendance || !todayAttendance.clock_in" 
                                @click="clockIn" 
                                :disabled="isLocating"
                                class="bg-indigo-600 hover:bg-gray-900 text-white px-8 py-3.5 rounded-2xl font-black shadow-lg hover:shadow-xl transition-all flex items-center gap-3 active:scale-95 group disabled:opacity-50">
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

                <!-- Detailed Attendance Logs -->
                <DataTable 
                    :headers="[
                        { key: 'user', label: 'Employee', sortable: true },
                        { key: 'date', label: 'Date', sortable: true },
                        { key: 'clock_in', label: 'Clock In' },
                        { key: 'clock_out', label: 'Clock Out' },
                        { key: 'status', label: 'Status' }
                    ]"
                    :items="attendances.data"
                    placeholder="Search by identity or employee contact..."
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
                                <span class="w-4 h-[1px] bg-gray-200"></span> Null Identity
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
                                <span class="w-4 h-[1px] bg-gray-200"></span> Still Operating
                            </span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap text-right">
                            <span class="px-5 py-2 inline-flex text-[9px] font-black rounded-2xl uppercase tracking-[0.2em] shadow-sm border transition-all" :class="{
                                'bg-emerald-50 text-emerald-700 border-emerald-100': log.status === 'present',
                                'bg-rose-50 text-rose-700 border-rose-100': log.status === 'absent',
                                'bg-amber-50 text-amber-700 border-amber-100': log.status === 'half-day'
                            }">
                                {{ log.status }}
                            </span>
                        </td>
                    </template>
                </DataTable>

                <div class="mt-8">
                    <Pagination :links="attendances.links" />
                </div>
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
