<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    taskStats: Array,
    topPerformers: Array,
    projectHealth: Array,
    attendanceTrend: Array
});

const getStatusColor = (status) => {
    return {
        'completed': 'bg-green-500',
        'in_progress': 'bg-blue-500',
        'pending': 'bg-amber-500',
    }[status] || 'bg-gray-500';
};
</script>

<template>
    <Head title="Performance Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Performance Analytics</h2>
        </template>

        <div class="py-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Task Distribution -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Task Distribution</h3>
                        <div class="space-y-6">
                            <div v-for="stat in taskStats" :key="stat.status">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-black uppercase tracking-tight text-gray-700">{{ stat.status.replace('_', ' ') }}</span>
                                    <span class="text-xs font-black text-gray-900">{{ stat.count }} Tasks</span>
                                </div>
                                <div class="w-full bg-gray-50 h-3 rounded-full overflow-hidden">
                                    <div :class="getStatusColor(stat.status)" class="h-full transition-all duration-1000" :style="{ width: (stat.count / taskStats.reduce((a,b) => a + b.count, 0) * 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Trend -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Attendance (Last 7 Days)</h3>
                        <div class="flex items-end justify-between h-48 gap-2 px-2">
                            <div v-for="day in attendanceTrend" :key="day.date" class="flex-1 flex flex-col items-center gap-3 group">
                                <div class="w-full bg-indigo-50 group-hover:bg-indigo-100 rounded-xl transition-all duration-500 relative flex items-end justify-center" :style="{ height: (day.count * 20) + 'px' }">
                                    <span class="absolute -top-6 text-[10px] font-black text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity">{{ day.count }}</span>
                                </div>
                                <span class="text-[9px] font-black text-gray-400 uppercase rotate-45 mt-4">{{ new Date(day.date).toLocaleDateString('en-IN', {day:'numeric', month:'short'}) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Top Performers -->
                    <div class="lg:col-span-1 bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Monthly Top Performers</h3>
                        <div class="space-y-5">
                            <div v-for="(user, index) in topPerformers" :key="user.id" class="flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 transition-colors">
                                <div class="relative">
                                    <img :src="'https://ui-avatars.com/api/?name='+user.name+'&background=random&color=fff'" class="w-10 h-10 rounded-full border-2 border-white shadow-sm" />
                                    <span class="absolute -top-1 -left-1 w-5 h-5 bg-amber-400 text-white rounded-full flex items-center justify-center text-[10px] font-black border-2 border-white shadow-sm">{{ index + 1 }}</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-black text-gray-900 uppercase tracking-tight">{{ user.name }}</p>
                                    <p class="text-[10px] text-[#2CA01C] font-bold uppercase tracking-widest">{{ user.tasks_count }} Tasks Finished</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Health -->
                    <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Project Health (Completion Velocity)</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div v-for="proj in projectHealth" :key="proj.id" class="p-6 rounded-3xl border border-gray-50 bg-gray-50/30">
                                <div class="flex justify-between items-start mb-4">
                                    <p class="text-[11px] font-black text-gray-800 uppercase tracking-tight leading-tight max-w-[70%]">{{ proj.name }}</p>
                                    <span class="text-xs font-black text-indigo-600">{{ proj.rate }}%</span>
                                </div>
                                <div class="w-full bg-white h-2 rounded-full overflow-hidden shadow-inner border border-gray-100">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-1000" :style="{ width: proj.rate + '%' }"></div>
                                </div>
                                <p class="text-[9px] text-gray-400 font-bold uppercase mt-3 tracking-widest">{{ proj.completed }} / {{ proj.tasks_count }} Tasks Done</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
