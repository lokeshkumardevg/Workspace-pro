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

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <DataTable 
                    :headers="[
                        { key: 'name', label: 'User Details', sortable: true },
                        { key: 'allowed_ip', label: 'Security (IP Mapping)' },
                        { key: 'roles', label: 'Authority Roles' },
                        { key: 'actions', label: 'Governance' }
                    ]"
                    :items="users.data"
                    placeholder="Search by identity or contact email..."
                    @search="val => search = val"
                >
                    <template #row="{ item: user }">
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-12 w-12 flex-shrink-0 relative">
                                    <img class="h-12 w-12 rounded-[1.2rem] border-2 border-white shadow-md ring-1 ring-gray-100" :src="'https://ui-avatars.com/api/?name='+user.name+'&background=random'" alt="Avatar">
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white shadow-sm"></div>
                                </div>
                                <div class="ml-4 text-left">
                                    <div class="text-sm font-black text-gray-900 uppercase tracking-tight leading-none">{{ user.name }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-widest">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div v-if="user.allowed_ip" class="inline-flex items-center gap-2 px-4 py-1.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                <span class="font-mono text-[10px] font-black uppercase tracking-widest">{{ user.allowed_ip }}</span>
                            </div>
                            <span v-else class="text-[9px] font-black text-gray-300 uppercase tracking-[0.2em] italic">Open Integration (No IP)</span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-sm">
                                    {{ role.name }}
                                </span>
                                <span v-if="user.roles.length === 0" class="text-[8px] font-black text-gray-300 uppercase tracking-widest italic">No Access Assigned</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap text-right">
                            <button @click="openEditModal(user)" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-indigo-600 text-white px-5 py-2.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-lg active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Configure Profile
                            </button>
                        </td>
                    </template>
                </DataTable>

                <Pagination :links="users.links" class="mt-8" />
            </div>
        </div>

        <!-- Governance Modal (Edit) -->
        <Modal :show="showEditModal" @close="showEditModal = false" title="Member Governance Profile" maxWidth="3xl">
            <form @submit.prevent="submitUpdate" class="space-y-8">
                <div class="flex items-center gap-6 p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 shadow-inner">
                    <img class="h-20 w-20 rounded-[1.8rem] border-4 border-white shadow-xl" :src="'https://ui-avatars.com/api/?name='+editingUser?.name+'&background=random'" alt="Avatar">
                    <div>
                        <h4 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">{{ editingUser?.name }}</h4>
                        <div class="flex items-center gap-3 mt-1.5">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">{{ editingUser?.email }}</span>
                            <span class="h-1.5 w-1.5 bg-gray-200 rounded-full"></span>
                            <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">System Identifier: #{{ editingUser?.id }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-8">
                         <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] flex items-center gap-3 ml-1">
                            <span class="h-1.5 w-1.5 bg-indigo-600 rounded-full"></span> Core Identity
                        </h5>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 ml-2">Display Name</label>
                            <input v-model="form.name" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 ml-2">Authentication Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 ml-2">Security Authorization (Allowed IP)</label>
                            <input v-model="form.allowed_ip" type="text" placeholder="Quantum Safe IP, e.g. 192.168.1.1" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-bold shadow-inner py-3.5 font-mono">
                            <p class="text-[9px] text-gray-400 font-bold mt-3 ml-2 uppercase leading-relaxed font-mono">Restricts clock-in functionality to specified IP network if defined.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] flex items-center gap-3 ml-1">
                            <span class="h-1.5 w-1.5 bg-indigo-600 rounded-full"></span> Privilege Hierarchy (Roles)
                        </h5>
                        <div class="max-h-[340px] overflow-y-auto space-y-3 pr-2 custom-scrollbar p-1">
                            <label v-for="role in roles" :key="role.id" class="flex items-center justify-between px-6 py-4 rounded-[1.8rem] border border-gray-100 cursor-pointer transition-all duration-300" :class="form.roles.includes(role.name) ? 'bg-indigo-600 text-white border-indigo-600 shadow-xl shadow-indigo-100' : 'bg-white text-gray-600 hover:border-indigo-200 hover:bg-gray-50'">
                                <span class="text-xs font-black uppercase tracking-widest">{{ role.name }}</span>
                                <input type="checkbox" :value="role.name" v-model="form.roles" class="h-5 w-5 rounded-lg border-gray-300 text-gray-900 focus:ring-0 transition-all checked:bg-white checked:border-white">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-50">
                    <button type="button" @click="showEditModal = false" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all active:scale-95">Abandon Changes</button>
                    <button type="submit" :disabled="form.processing" class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-950 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-200 active:scale-95">Commit Governance</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
</style>
