<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    roles: Object,
    allPermissions: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce(function (value) {
    router.get(route('roles.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const editingRole = ref(null);

const form = useForm({
    name: '',
    permissions: []
});

const openModal = (role = null) => {
    if (role) {
        editingRole.value = role;
        form.name = role.name;
        form.permissions = role.permissions.map(p => p.name);
    } else {
        editingRole.value = null;
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (editingRole.value) {
        form.put(route('roles.update', editingRole.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteRole = (role) => {
    if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
        router.delete(route('roles.destroy', role.id));
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingRole.value = null;
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Roles & Permissions
                </h2>
                <button @click="openModal()" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-6 py-2 rounded-xl font-bold shadow-md transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    New Role
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    
                    <!-- Toolbar -->
                    <div class="p-6 bg-white border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-gray-900 font-semibold text-lg">Manage Access Roles</div>
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Search roles..." class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-[#2CA01C] focus:border-[#2CA01C] sm:text-sm shadow-sm transition-colors text-gray-700" />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role Name</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned Permissions</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="role in roles.data" :key="role.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-black mr-3 shadow-inner">
                                                {{ role.name.charAt(0) }}
                                            </div>
                                            <div class="text-sm font-bold text-gray-900">{{ role.name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="perm in role.permissions" :key="perm.id" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-100 uppercase tracking-tighter">
                                                {{ perm.name }}
                                            </span>
                                            <span v-if="role.permissions.length === 0" class="text-gray-400 text-xs italic">No specific permissions</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openModal(role)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 font-bold text-xs uppercase transition-all">
                                                Edit
                                            </button>
                                            <button v-if="role.name !== 'Super Admin'" @click="deleteRole(role)" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1.5 rounded-lg border border-red-100 font-bold text-xs uppercase transition-all">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="roles.links" class="mt-6" />
            </div>
        </div>

        <!-- Role Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                    <form @submit.prevent="submit">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-8">
                            <h3 class="text-2xl leading-6 font-black text-gray-900 mb-6 flex items-center gap-3">
                                <span class="bg-[#2CA01C]/10 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-[#2CA01C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                                {{ editingRole ? 'Edit Role' : 'Create New Role' }}
                            </h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Role Name</label>
                                    <input v-model="form.name" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#2CA01C] focus:border-[#2CA01C] font-bold" required placeholder="e.g. Project Manager" :disabled="editingRole?.name === 'Super Admin'" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Assign Permissions</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 bg-gray-50 p-4 rounded-xl border border-gray-200 overflow-y-auto max-h-72 custom-scrollbar">
                                        <label v-for="perm in allPermissions" :key="perm.id" class="flex items-center p-3 rounded-lg border border-transparent hover:bg-white hover:shadow-sm cursor-pointer transition-all" :class="{'bg-white border-green-200 shadow-sm ring-1 ring-green-100': form.permissions.includes(perm.name)}">
                                            <input type="checkbox" :value="perm.name" v-model="form.permissions" class="rounded border-gray-300 text-[#2CA01C] focus:ring-[#2CA01C] mr-3 h-4 w-4">
                                            <span class="text-xs font-bold text-gray-700 capitalize break-words">{{ perm.name }}</span>
                                        </label>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-2 italic">* Super Admin will always have all permissions regardless of the checkboxes.</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl gap-3">
                            <button type="submit" :disabled="form.processing" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg text-sm disabled:opacity-50">
                                {{ editingRole ? 'Update Role' : 'Create Role' }}
                            </button>
                            <button type="button" @click="closeModal()" class="bg-white border border-gray-200 text-gray-700 px-8 py-2.5 rounded-xl font-bold hover:bg-gray-50 transition-all text-sm">
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
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
</style>
