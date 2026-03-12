<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import Toast from '@/Components/Toast.vue';
import { Link, router } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const showingUserManagement = ref(false);
const showingAttendance = ref(false);

const isUserRoute = () => route().current('users.*') || route().current('roles.*');
const isAttendanceRoute = () => route().current('attendance.index') || route().current('attendance.report');

showingUserManagement.value = isUserRoute();
showingAttendance.value = isAttendanceRoute();

const markAsRead = (notif) => {
    router.post(route('notifications.read', notif.id), {}, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};
</script>

<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden font-sans">

        <!-- Toast Notifications -->
        <Toast />

        <!-- Sidebar -->
        <aside class="flex-shrink-0 w-64 bg-indigo-900 border-r border-indigo-800 shadow-xl hidden md:flex flex-col transition-all duration-300">
            <div class="h-16 flex items-center justify-center border-b border-indigo-800 px-4 bg-indigo-950">
                <Link :href="route('dashboard')" class="flex items-center gap-3 w-full justify-center">
                    <ApplicationLogo class="block h-8 w-auto fill-current text-white" />
                    <span class="text-white font-bold text-lg tracking-wide">WorkSpace Pro</span>
                </Link>
            </div>

            <div class="flex-1 overflow-y-auto mt-4 scrollbar-thin">
                <nav class="px-3 space-y-1 text-sm font-medium">

                    <!-- Dashboard -->
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                        Dashboard
                    </NavLink>

                    <!-- Announcements -->
                    <NavLink :href="route('announcements.index')" :active="route().current('announcements.*')" class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
                        Broadcasting
                    </NavLink>

                    <!-- Projects -->
                    <NavLink v-if="$page.props.auth.user.permissions.includes('manage projects') || $page.props.auth.user.roles.some(r => ['Super Admin', 'Admin', 'Manager', 'Team Lead', 'Leader'].includes(r))"
                        :href="route('projects.index')" :active="route().current('projects.index')" class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        Projects
                    </NavLink>
                    
                    <!-- Tasks -->
                    <NavLink v-if="$page.props.auth.user.permissions.includes('view tasks') || $page.props.auth.user.roles.some(r => ['Super Admin', 'Admin', 'Manager', 'Team Lead', 'Leader'].includes(r))"
                        :href="route('tasks.index')" :active="route().current('tasks.index')" 
                        class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Tasks
                    </NavLink>

                    <!-- Leads CRM -->
                    <NavLink v-if="$page.props.auth.user.permissions.includes('view leads') || $page.props.auth.user.roles.some(r => ['Super Admin', 'Admin', 'Manager', 'Team Lead', 'Leader'].includes(r))"
                        :href="route('leads.index')" :active="route().current('leads.index')" class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Leads CRM
                    </NavLink>

                    <!-- Analytics/Performance -->
                    <NavLink v-if="$page.props.auth.user.permissions.includes('manage tasks') || $page.props.auth.user.roles.some(r => ['Super Admin', 'Admin', 'Manager', 'Team Lead', 'Leader'].includes(r))"
                        :href="route('analytics.index')" :active="route().current('analytics.*')" 
                        class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Performance
                    </NavLink>

                    <!-- Attendance (Submenu for HR/Admin) -->
                    <div v-if="$page.props.auth.user.permissions.includes('view attendance') || $page.props.auth.user.roles.includes('Super Admin')">
                        <button @click="showingAttendance = !showingAttendance"
                            class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center justify-between transition-colors focus:outline-none">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Attendance
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showingAttendance }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div v-show="showingAttendance" class="mt-1 space-y-1 pl-10">
                            <NavLink :href="route('attendance.index')" :active="route().current('attendance.index')" class="w-full text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg px-3 py-2 flex items-center text-sm transition-colors gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                Daily Logs
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.permissions.includes('download reports') || $page.props.auth.user.roles.includes('Super Admin')"
                                     :href="route('attendance.report')" :active="route().current('attendance.report')" class="w-full text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg px-3 py-2 flex items-center text-sm transition-colors gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Monthly Reports
                            </NavLink>
                        </div>
                    </div>

                    <!-- Leave Management -->
                    <NavLink v-if="$page.props.auth.user.permissions.includes('view leaves') || $page.props.auth.user.permissions.includes('manage leaves') || $page.props.auth.user.roles.includes('Super Admin')"
                        :href="route('leaves.index')" :active="route().current('leaves.*')" class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center gap-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Leave Management
                        <!-- Pending badge -->
                        <span v-if="$page.props.auth.user.permissions.includes('approve leaves') && $page.props.stats?.pending_leaves > 0"
                               class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">
                            {{ $page.props.stats?.pending_leaves }}
                        </span>
                    </NavLink>

                    <!-- Divider -->
                    <div class="my-3 border-t border-indigo-800/60"></div>

                    <!-- User Management Submenu -->
                    <div v-if="$page.props.auth.user.permissions.includes('manage users') || $page.props.auth.user.roles.includes('Super Admin')">
                        <button @click="showingUserManagement = !showingUserManagement"
                            class="w-full text-indigo-100 hover:bg-indigo-800 hover:text-white rounded-lg px-3 py-2.5 flex items-center justify-between transition-colors focus:outline-none">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                User Management
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showingUserManagement }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div v-show="showingUserManagement" class="mt-1 space-y-1 pl-10">
                            <NavLink :href="route('users.index')" :active="route().current('users.*')" class="text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg px-3 py-2 flex items-center text-sm transition-colors gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Users
                            </NavLink>
                            <NavLink :href="route('roles.index')" :active="route().current('roles.*')" class="text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg px-3 py-2 flex items-center text-sm transition-colors gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Roles & Permissions
                            </NavLink>
                        </div>
                    </div>

                </nav>
            </div>

            <!-- Sidebar User Footer -->
            <div class="p-3 border-t border-indigo-800">
                <div class="flex items-center gap-3 p-2 rounded-xl bg-indigo-800/60 text-white hover:bg-indigo-800 transition-colors cursor-default">
                    <img class="w-9 h-9 rounded-full border-2 border-indigo-400"
                         :src="'https://ui-avatars.com/api/?name='+$page.props.auth.user.name+'&background=2CA01C&color=fff'"
                         alt="User Avatar" />
                    <div class="overflow-hidden flex-1">
                        <div class="text-sm font-bold truncate">{{ $page.props.auth.user.name }}</div>
                        <div class="text-xs text-indigo-300 truncate">{{ $page.props.auth.user.roles[0] || 'User' }}</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <header class="h-16 bg-white border-b shadow-sm flex items-center justify-between px-4 sm:px-6 z-10">
                <!-- Mobile Menu Button -->
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="md:hidden p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{ 'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="flex-1 overflow-hidden ml-4 md:ml-0 flex items-center">
                    <slot name="header" />
                </div>

                <!-- Right: Notifications + User -->
                <div class="flex items-center gap-2">

                    <!-- Notification Bell -->
                    <Dropdown v-if="$page.props.auth.user" align="right" width="64">
                        <template #trigger>
                            <button class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100 transition-colors focus:outline-none">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                <span v-if="$page.props.auth.user.unread_notifications?.length > 0"
                                       class="absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] font-bold text-white flex items-center justify-center animate-bounce">
                                    {{ $page.props.auth.user.unread_notifications.length }}
                                </span>
                            </button>
                        </template>
                        <template #content>
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-900">Notifications</h3>
                                <button v-if="$page.props.auth.user.unread_notifications?.length > 0" 
                                        @click="markAllAsRead" 
                                        class="text-[9px] font-black text-indigo-600 uppercase hover:underline">Mark all read</button>
                            </div>
                            <div class="max-h-80 overflow-y-auto scrollbar-thin">
                                <div v-for="notif in $page.props.auth.user.unread_notifications" :key="notif.id" 
                                     class="p-4 border-b border-gray-50 hover:bg-gray-50 transition-colors cursor-pointer group"
                                     @click="markAsRead(notif)">
                                    <div class="flex items-start gap-3">
                                        <div class="h-2 w-2 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800 leading-tight group-hover:text-indigo-600 transition-colors">{{ notif.data.title || 'New Notification' }}</p>
                                            <p class="text-[10px] text-gray-500 mt-1 line-clamp-2 leading-relaxed">{{ notif.data.message }}</p>
                                            <p class="text-[8px] text-gray-400 mt-2 uppercase font-black tracking-tighter">{{ new Date(notif.created_at).toLocaleString() }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="$page.props.auth.user.unread_notifications?.length === 0" class="p-8 text-center opacity-30">
                                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    <p class="text-[10px] font-black uppercase tracking-widest">No new alerts</p>
                                </div>
                            </div>
                        </template>
                    </Dropdown>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button type="button" class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-200 text-sm font-semibold rounded-xl text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none transition-colors">
                                <img class="w-7 h-7 rounded-full" :src="'https://ui-avatars.com/api/?name='+$page.props.auth.user.name+'&background=2CA01C&color=fff'" alt="Avatar" />
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </template>
                        <template #content>
                            <div class="px-4 py-2 text-xs text-gray-400 border-b">{{ $page.props.auth.user.email }}</div>
                            <DropdownLink :href="route('profile.edit')">Profile Settings</DropdownLink>
                            <DropdownLink v-if="$page.props.auth.user.permissions.includes('view leaves') || $page.props.auth.user.permissions.includes('manage leaves')" :href="route('leaves.index')">My Leaves</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Mobile Sub-menu -->
            <div v-if="showingNavigationDropdown" class="md:hidden absolute top-16 left-0 w-full bg-indigo-900 border-b z-20 shadow-xl">
                <nav class="px-4 py-3 space-y-1 text-white text-sm">
                    <a :href="route('dashboard')" class="block px-3 py-2 rounded-md hover:bg-indigo-800">Dashboard</a>
                    <a v-if="$page.props.auth.user.permissions.includes('view tasks') || $page.props.auth.user.roles.includes('Super Admin')"
                       :href="route('tasks.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-800">Tasks</a>
                    <a :href="route('attendance.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-800">Attendance</a>
                    <a :href="route('leaves.index')" class="block px-3 py-2 rounded-md hover:bg-indigo-800">Leave Management</a>
                </nav>
            </div>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50/50 fade-in-up">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.fade-in-up {
    animation: fadeInUp 0.35s ease-out forwards;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
