<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
});

const now = new Date();
const hours = now.getHours();
const greeting = computed(() => {
    if (hours < 12) return 'Good Morning';
    if (hours < 17) return 'Good Afternoon';
    return 'Good Evening';
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-900">
                Home
            </h2>
        </template>

        <div class="py-6 px-1">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Greeting Banner -->
                <div class="relative bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 via-transparent to-purple-50/50 pointer-events-none"></div>
                    <div class="px-8 py-7 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div>
                            <p class="text-indigo-600 font-black text-[10px] uppercase tracking-widest mb-1">
                                {{ greeting }},
                            </p>
                            <h1 class="text-3xl font-black text-gray-900 leading-tight tracking-tight">{{ $page.props.auth.user.name }}</h1>
                            <p class="text-gray-500 mt-2 text-sm max-w-lg font-medium">Welcome back to your dashboard. Stay updated with your progress.</p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="text-[10px] bg-indigo-50 text-indigo-600 border border-indigo-100 px-4 py-2 rounded-xl font-black uppercase tracking-widest inline-flex items-center gap-2 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                {{ $page.props.auth.user.roles[0] || 'User' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div>
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 px-1">Quick Actions</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">

                        <Link v-if="stats.hasOwnProperty('projects')" :href="route('projects.index')" class="group bg-white border border-gray-100 rounded-2xl p-5 flex flex-col items-start gap-3 hover:border-indigo-600 hover:shadow-md transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 group-hover:bg-indigo-600 font-black text-indigo-600 group-hover:text-white flex items-center justify-center transition-all">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-[#0077C5] transition-colors">Projects</p>
                                <p class="text-xs text-gray-500 mt-0.5">Manage all your projects</p>
                            </div>
                        </Link>

                        <Link v-if="stats.hasOwnProperty('tasks')" :href="route('tasks.index')" class="group bg-white border border-gray-100 rounded-2xl p-5 flex flex-col items-start gap-3 hover:border-indigo-600 hover:shadow-md transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 group-hover:bg-indigo-600 font-black text-indigo-600 group-hover:text-white flex items-center justify-center transition-all">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138-3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-[#2CA01C] transition-colors">Tasks</p>
                                <p class="text-xs text-gray-500 mt-0.5">Track your work items</p>
                            </div>
                        </Link>

                        <Link v-if="stats.hasOwnProperty('leads')" :href="route('leads.index')" class="group bg-white border border-gray-200 rounded-2xl p-5 flex flex-col items-start gap-3 hover:border-purple-500 hover:shadow-md transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-purple-600 transition-colors">Leads CRM</p>
                                <p class="text-xs text-gray-500 mt-0.5">Manage sales pipeline</p>
                            </div>
                        </Link>

                        <Link :href="route('attendance.index')" class="group bg-white border border-gray-200 rounded-2xl p-5 flex flex-col items-start gap-3 hover:border-orange-400 hover:shadow-md transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 group-hover:bg-orange-100 flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-orange-500 transition-colors">Attendance</p>
                                <p class="text-xs text-gray-500 mt-0.5">Clock in / out records</p>
                            </div>
                        </Link>

                        <Link :href="route('leaves.index')" class="group bg-white border border-gray-200 rounded-2xl p-5 flex flex-col items-start gap-3 hover:border-rose-500 hover:shadow-md transition-all duration-200 relative">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 group-hover:bg-rose-100 flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-rose-500 transition-colors">Leave Management</p>
                                <p class="text-xs text-gray-500 mt-0.5">Apply &amp; track leaves</p>
                            </div>
                            <span v-if="stats?.pending_leaves > 0" class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center animate-bounce">
                                {{ stats.pending_leaves }}
                            </span>
                        </Link>

                    </div>
                </div>

                <!-- KPI Stats -->
                <div>
                    <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 px-1">Overview</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                        <!-- Stat 1: Projects -->
                        <div v-if="stats.hasOwnProperty('projects')" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Projects</span>
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#0077C5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                </div>
                            </div>
                            <p class="text-4xl font-black text-gray-900">{{ stats?.projects ?? 0 }}</p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">All time</span>
                                <span class="text-xs text-[#0077C5] font-semibold">View →</span>
                            </div>
                            <div class="mt-3 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-1 bg-[#0077C5] rounded-full" style="width: 68%"></div>
                            </div>
                        </div>

                        <!-- Stat 2: Tasks -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tasks</span>
                                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#2CA01C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                            </div>
                            <p class="text-4xl font-black text-gray-900">{{ stats?.tasks ?? 0 }}</p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">Assigned</span>
                                <span class="text-xs text-[#2CA01C] font-semibold">View →</span>
                            </div>
                            <div class="mt-3 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-1 bg-[#2CA01C] rounded-full" style="width: 42%"></div>
                            </div>
                        </div>

                        <!-- Stat 3: Leads -->
                        <div v-if="stats.hasOwnProperty('leads')" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">CRM Leads</span>
                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                            </div>
                            <p class="text-4xl font-black text-gray-900">{{ stats?.leads ?? 0 }}</p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">In pipeline</span>
                                <span class="text-xs text-purple-600 font-semibold">View →</span>
                            </div>
                            <div class="mt-3 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-1 bg-purple-500 rounded-full" style="width: 55%"></div>
                            </div>
                        </div>

                        <!-- Stat 4: Attendance -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Present Today</span>
                                <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                            <p class="text-4xl font-black text-gray-900">{{ stats?.attendance_today ?? 0 }}</p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">Staff clocked in</span>
                                <span class="text-xs text-orange-500 font-semibold">View →</span>
                            </div>
                            <div class="mt-3 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-1 bg-orange-400 rounded-full" style="width: 30%"></div>
                            </div>
                        </div>

                        <!-- Stat 5: Pending Leaves -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                            <div v-if="stats?.pending_leaves > 0" class="absolute top-0 right-0 w-16 h-16 bg-red-500/10 rounded-bl-full"></div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pending Leaves</span>
                                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                            <p class="text-4xl font-black" :class="stats?.pending_leaves > 0 ? 'text-red-600' : 'text-gray-900'">{{ stats?.pending_leaves ?? 0 }}</p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">Awaiting review</span>
                                <span class="text-xs text-rose-500 font-semibold">Review →</span>
                            </div>
                            <div class="mt-3 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-1 bg-rose-500 rounded-full transition-all" :style="{ width: Math.min((stats?.pending_leaves ?? 0) * 10, 100) + '%' }"></div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Advanced Middle Row: Project Progress -->
                <div v-if="stats.project_progress?.length > 0">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 px-1 text-indigo-900 border-l-4 border-indigo-600 pl-3 ml-1">Project Progress</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="proj in stats.project_progress" :key="proj.id" class="bg-white border border-gray-200 rounded-3xl p-5 shadow-sm hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black text-gray-900 uppercase tracking-tight truncate max-w-[120px] group-hover:text-indigo-600 transition-colors">{{ proj.name }}</span>
                                <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">{{ proj.percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-50 h-2 rounded-full overflow-hidden mb-3 shadow-inner">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-1000 shadow-[0_0_10px_rgba(79,70,229,0.3)]" :style="{ width: proj.percentage + '%' }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">{{ proj.completed_tasks_count }} / {{ proj.tasks_count }} Tasks Done</p>
                                <svg class="w-3 h-3 text-gray-300 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Activity + Profile  -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Real Activity Feed -->
                    <div class="lg:col-span-2 bg-white border border-gray-100 rounded-[2rem] shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-shadow">
                        <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
                            <h3 class="font-black text-gray-900 text-[10px] uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                                Recent Activity Feed
                            </h3>
                            <span class="text-[10px] font-black text-indigo-600 bg-white px-3 py-1 rounded-xl shadow-sm border border-gray-100 uppercase">Updates</span>
                        </div>
                        <div class="divide-y divide-gray-50 flex-1 overflow-y-auto max-h-[400px] scrollbar-thin">
                            <div v-for="activity in stats.recent_activity" :key="activity.id" class="px-8 py-5 flex items-start gap-5 hover:bg-gray-50/50 transition-colors group">
                                <div class="w-11 h-11 rounded-2xl bg-white shadow-sm flex-shrink-0 border border-gray-100 p-0.5 overflow-hidden group-hover:border-indigo-200 transition-colors">
                                     <img :src="'https://ui-avatars.com/api/?name='+activity.user.name+'&background=random&color=fff'" class="w-full h-full rounded-[14px]" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-black text-gray-900 leading-tight">
                                            {{ activity.user.name }} 
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mx-1 opacity-60">On task</span> 
                                            <span class="text-indigo-600 group-hover:underline cursor-pointer">{{ activity.task.title }}</span>
                                        </p>
                                        <span class="text-[9px] text-gray-300 font-black uppercase tracking-tighter">{{ new Date(activity.created_at).toLocaleTimeString() }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 line-clamp-2 italic font-medium leading-relaxed bg-gray-50/50 p-2 rounded-xl mt-2 border border-gray-100/50 group-hover:bg-white transition-colors">"{{ activity.comment }}"</p>
                                    <div v-if="activity.attachment" class="flex items-center gap-1.5 text-[8px] font-black text-[#2CA01C] uppercase tracking-widest mt-3 bg-green-50 w-max px-2.5 py-1 rounded-full border border-green-100">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Screenshot Attached
                                    </div>
                                </div>
                            </div>
                            <div v-if="stats.recent_activity?.length === 0" class="flex flex-col items-center justify-center py-28 opacity-20 bg-gray-50/20">
                                <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">No recent activity found</p>
                            </div>
                        </div>
                    </div>

                    <!-- Account Card (QuickBooks-style right panel) -->
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 text-sm">Your Account</h3>
                        </div>
                        <div class="flex-1 p-6 flex flex-col items-center text-center gap-4">
                            <img class="w-16 h-16 rounded-full border-4 border-white shadow-lg" :src="'https://ui-avatars.com/api/?name='+$page.props.auth.user.name+'&background=2CA01C&color=fff&size=128'" alt="User Avatar" />
                            <div>
                                <p class="font-black text-gray-900 text-lg uppercase tracking-tight">{{ $page.props.auth.user.name }}</p>
                                <p class="text-sm text-gray-500 font-medium">{{ $page.props.auth.user.email }}</p>
                                <span class="mt-2 inline-block text-[10px] font-black px-4 py-1.5 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 uppercase tracking-widest">
                                    {{ $page.props.auth.user.roles[0] || 'User' }}
                                </span>
                            </div>
                            <div class="w-full mt-2 space-y-2">
                                <Link :href="route('profile.edit')" class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-gray-900 text-white font-black rounded-xl py-3 text-[10px] uppercase tracking-widest transition-all shadow-md active:scale-95">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Edit Profile
                                </Link>
                                <Link v-if="$page.props.auth.user.permissions.includes('manage users') || $page.props.auth.user.roles.includes('Super Admin')" :href="route('users.index')" class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl py-2.5 text-sm transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Manage Users
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
