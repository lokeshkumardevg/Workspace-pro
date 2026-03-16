<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
    isSuperAdmin: Boolean,
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
    designation: '',
    employee_id: '',
    department: '',
    base_salary: 0,
    allowed_ip: '',
    attendance_bypass: false,
    joining_date: '',
    probation_months: 3,
    role: ''
});

const openEditModal = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.designation = user.designation || '';
    form.employee_id = user.employee_id || '';
    form.department = user.department || '';
    form.base_salary = user.base_salary || 0;
    form.allowed_ip = user.allowed_ip || '';
    form.attendance_bypass = !!user.attendance_bypass;
    form.joining_date = user.joining_date || '';
    form.probation_months = user.probation_months ?? 3;
    form.role = user.roles.length > 0 ? user.roles[0].name : '';
    showEditModal.value = true;
};

const submitUpdate = () => {
    form.put(route('users.update', editingUser.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};

const deleteUser = (user) => {
    if (confirm(`Are you sure you want to delete user "${user.name}"?`)) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (n) => 
    new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(n ?? 0);
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-xl font-bold text-gray-800">
                    Staff Management
                </h2>
                <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg font-bold text-xs uppercase shadow-sm">
                    {{ users.total }} Users Total
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <DataTable 
                    :headers="[
                        { key: 'name', label: 'Employee Name' },
                        { key: 'allowed_ip', label: 'Security (IP/Bypass)' },
                        { key: 'salary', label: 'Salary' },
                        { key: 'role', label: 'Role' },
                        { key: 'actions', label: 'Actions' }
                    ]"
                    :items="users.data"
                    placeholder="Search users..."
                    @search="val => search = val"
                >
                    <template #row="{ item: user }">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img class="h-10 w-10 rounded-full border border-gray-100 shadow-sm mr-3" 
                                    :src="'https://ui-avatars.com/api/?name='+user.name+'&background=4f46e5&color=fff'" alt="Avatar">
                                <div>
                                    <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                    <div class="text-[10px] text-gray-400 font-medium italic">
                                        {{ user.designation || 'Staff' }} • #{{ user.employee_id || user.id }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <div class="text-[10px] font-medium" :class="user.allowed_ip ? 'text-indigo-600' : 'text-gray-400'">
                                    IP: {{ user.allowed_ip || 'Any' }}
                                </div>
                                <div v-if="user.attendance_bypass" class="text-[9px] font-bold text-amber-600 uppercase tracking-tighter">
                                    🔓 Location Bypass ON
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-gray-700">{{ formatCurrency(user.base_salary) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span v-for="role in user.roles" :key="role.id" class="inline-flex px-2 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 mr-1">
                                {{ role.name }}
                            </span>
                            <span v-if="user.roles.length === 0" class="text-[10px] text-gray-300 italic">No Role</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEditModal(user)" class="bg-gray-800 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all active:scale-95">
                                    Edit
                                </button>
                                <button v-if="isSuperAdmin && $page.props.auth.user.id !== user.id" @click="deleteUser(user)" class="bg-rose-50 hover:bg-rose-600 hover:text-white px-4 py-2 rounded-lg text-xs font-bold transition-all text-rose-700 border border-rose-100">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </template>
                </DataTable>

                <div class="flex justify-end mt-6">
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" title="Edit Staff Profile" maxWidth="3xl">
            <form @submit.prevent="submitUpdate" class="p-6 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Full Name</label>
                        <input v-model="form.name" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Email Address</label>
                        <input v-model="form.email" type="email" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Employee ID</label>
                        <input v-model="form.employee_id" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Designation</label>
                        <input v-model="form.designation" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Salary (₹)</label>
                        <input v-model="form.base_salary" type="number" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Security Role</label>
                        <select v-model="form.role" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3">
                            <option value="">Select Role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">* Only one role can be assigned per staff.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Allowed IP Address</label>
                        <input v-model="form.allowed_ip" type="text" placeholder="Leave empty for all IPs" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm py-3">
                    </div>
                </div>

                <div v-if="isSuperAdmin" class="bg-gray-50 p-4 rounded-xl flex items-center justify-between border border-gray-100">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Attendance Location Bypass</p>
                        <p class="text-xs text-gray-500">Allow clock-in/out from any location (bypass GPS check)</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.attendance_bypass" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" @click="showEditModal = false" class="px-6 py-2 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 transition-all active:scale-95">Save Changes</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
