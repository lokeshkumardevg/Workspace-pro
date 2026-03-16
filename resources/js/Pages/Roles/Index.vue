<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
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

// Group permissions by prefix for better UI
const groupedPermissions = props.allPermissions.reduce((acc, perm) => {
    const parts = perm.name.split(' ');
    const group = parts.length > 1 ? parts.slice(1).join(' ') : 'Other';
    if (!acc[group]) acc[group] = [];
    acc[group].push(perm);
    return acc;
}, {});
</script>

<template>
    <Head title="Roles & Permissions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6 text-left">
                <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                    Roles & Permissions
                </h2>
                <button @click="openModal()" class="bg-[#2CA01C] hover:bg-[#238016] text-white px-6 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    Add Role
                </button>
            </div>
        </template>

        <div class="py-6 px-1">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <DataTable 
                    :headers="[
                        { key: 'name', label: 'Role Name' },
                        { key: 'permissions', label: 'Permissions' },
                        { key: 'actions', label: 'Actions' }
                    ]"
                    :items="roles.data"
                    placeholder="Search roles..."
                    @search="val => search = val"
                >
                    <template #row="{ item: role }">
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-[1.2rem] bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-xs uppercase shadow-sm">
                                    {{ role.name.charAt(0) }}
                                </div>
                                <div class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ role.name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-wrap gap-1.5 max-w-2xl">
                                <span v-for="perm in role.permissions" :key="perm.id" class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-xl text-[9px] font-black uppercase tracking-widest leading-none">
                                    {{ perm.name }}
                                </span>
                                <span v-if="role.permissions.length === 0" class="text-[9px] font-black text-gray-300 uppercase italic tracking-widest opacity-50 font-mono">No Permissions</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button @click="openModal(role)" class="bg-gray-50 hover:bg-indigo-600 hover:text-white px-5 py-2.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-90 border border-gray-100">
                                    Edit
                                </button>
                                <button v-if="role.name !== 'Super Admin'" @click="deleteRole(role)" class="bg-rose-50 hover:bg-rose-600 hover:text-white px-5 py-2.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-90 border border-rose-100 text-rose-700">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </template>
                </DataTable>

                <div class="flex justify-end pr-4 mt-4">
                    <Pagination :links="roles.links" />
                </div>
            </div>
        </div>

        <!-- Role Form Modal -->
        <Modal :show="showModal" @close="closeModal" :title="editingRole ? 'Edit Role' : 'Add New Role'" maxWidth="4xl">
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Role Name</label>
                    <input v-model="form.name" type="text" 
                        class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5 uppercase tracking-wide" 
                        required placeholder="e.g. MANAGER" 
                        :disabled="editingRole?.name === 'Super Admin'" />
                    <p v-if="form.errors.name" class="mt-2 text-[10px] text-red-600 font-black uppercase tracking-widest ml-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-4 ml-1">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Assign Permissions</label>
                        <span class="text-[9px] font-black text-indigo-500 bg-indigo-50 px-3 py-1 rounded-lg border border-indigo-100 uppercase tracking-widest">
                            {{ form.permissions.length }} Selected
                        </span>
                    </div>

                    <div class="bg-gray-50 rounded-3xl border border-gray-100 p-8 space-y-8 max-h-[500px] overflow-y-auto custom-scrollbar shadow-inner">
                        <div v-for="(perms, group) in groupedPermissions" :key="group" class="space-y-4">
                            <h4 class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em] flex items-center gap-3">
                                <span class="h-px bg-gray-200 flex-grow"></span>
                                {{ group }} MODULE
                                <span class="h-px bg-gray-200 flex-grow"></span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <label v-for="perm in perms" :key="perm.id" 
                                    class="flex items-center p-4 rounded-2xl border transition-all duration-300 group cursor-pointer"
                                    :class="form.permissions.includes(perm.name) 
                                        ? 'bg-emerald-600 border-white shadow-lg shadow-emerald-100 ring-4 ring-emerald-50' 
                                        : 'bg-white border-white hover:border-gray-200 shadow-sm hover:shadow-md'">
                                    
                                    <div class="relative flex items-center justify-center h-5 w-5 mr-3">
                                        <input type="checkbox" :value="perm.name" v-model="form.permissions" 
                                            class="peer opacity-0 h-5 w-5 cursor-pointer z-10" />
                                        <div class="absolute inset-0 h-5 w-5 bg-gray-100 rounded-lg border border-gray-200 transition-all peer-checked:bg-white peer-checked:border-white"></div>
                                        <svg v-if="form.permissions.includes(perm.name)" class="absolute w-3.5 h-3.5 text-emerald-600 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>

                                    <span class="text-[10px] font-black uppercase tracking-widest transition-colors"
                                        :class="form.permissions.includes(perm.name) ? 'text-white' : 'text-gray-600'">
                                        {{ perm.name }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-50">
                    <button type="button" @click="closeModal()" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="form.processing" 
                            class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-950 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : (editingRole ? 'Update Role' : 'Save Role') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
</style>
