<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce(function (value) {
    router.get(route('users.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showEditModal = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    allowed_ip: '',
    roles: []
});

const openEditModal = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.allowed_ip = user.allowed_ip || '';
    form.roles = user.roles.map(r => r.name);
    showEditModal.value = true;
};

const submitUpdate = () => {
    form.put(route('users.update', editingUser.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};
</script>

<template>
    <Head title="Users Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-800">
                User Management
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Table Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    
                    <!-- Toolbar -->
                    <div class="p-6 bg-white border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-gray-900 font-semibold text-lg">System Users</div>
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Search by name or email..." class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-[#2CA01C] focus:border-[#2CA01C] sm:text-sm shadow-sm transition-colors text-gray-700" />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User Details</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Allowed IP</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Roles</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img class="h-10 w-10 rounded-full border-2 border-indigo-100 shadow-sm" :src="'https://ui-avatars.com/api/?name='+user.name+'&background=random'" alt="Avatar">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="user.allowed_ip" class="px-2 py-1 bg-green-50 text-green-700 font-mono text-xs rounded border border-green-100 font-bold">
                                            {{ user.allowed_ip }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs italic">No IP Restriction</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200 shadow-sm">
                                                {{ role.name }}
                                            </span>
                                            <span v-if="user.roles.length === 0" class="text-gray-400 text-xs italic">No Roles Assigned</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors border border-indigo-200 font-semibold shadow-sm inline-flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            Edit / Roles
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500 font-medium">No users found fetching matching criteria.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="users.links" class="mt-6" />
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="showEditModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                    <form @submit.prevent="submitUpdate">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div class="flex items-start gap-4 mb-5 pb-5 border-b border-gray-100">
                                <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold flex-shrink-0 text-xl overflow-hidden shadow-inner">
                                    <img :src="'https://ui-avatars.com/api/?name='+editingUser?.name+'&background=random'" alt="Avatar">
                                </div>
                                <div>
                                    <h3 class="text-xl leading-6 font-bold text-gray-900">Edit User Details</h3>
                                    <p class="text-sm text-gray-500 mt-1">Manage details and roles for <span class="font-semibold text-indigo-600">{{ editingUser?.name }}</span></p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Full Name</label>
                                        <input v-model="form.name" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#2CA01C] focus:border-[#2CA01C]" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Email Address</label>
                                        <input v-model="form.email" type="email" class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#2CA01C] focus:border-[#2CA01C]" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Allowed IP Address</label>
                                        <input v-model="form.allowed_ip" type="text" placeholder="e.g. 192.168.1.1" class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#2CA01C] focus:border-[#2CA01C] font-mono">
                                        <p class="text-[10px] text-gray-400 mt-1 italic leading-tight">Leave blank for no restriction. Employees can only clock in from this IP if set.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Assign Roles</label>
                                    <div class="max-h-60 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                        <label v-for="role in roles" :key="role.id" class="flex items-center justify-between p-2.5 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors" :class="{'bg-[#2CA01C]/5 border-[#2CA01C]/20': form.roles.includes(role.name)}">
                                            <span class="text-sm font-semibold text-gray-700">{{ role.name }}</span>
                                            <input type="checkbox" :value="role.name" v-model="form.roles" class="h-4 w-4 rounded border-gray-300 text-[#2CA01C] focus:ring-[#2CA01C]">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl gap-3">
                            <button type="submit" :disabled="form.processing" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-6 py-2 rounded-xl font-bold transition-all shadow-md text-sm">
                                Save Changes
                            </button>
                            <button type="button" @click="showEditModal = false" class="bg-white border border-gray-200 text-gray-700 px-6 py-2 rounded-xl font-bold hover:bg-gray-50 transition-all text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
</style>
