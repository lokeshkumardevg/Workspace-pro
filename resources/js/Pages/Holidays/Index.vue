<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    holidays: Object,
    filters: Object,
    canManage: Boolean,
    stats: Object,
});

const showModal = ref(false);
const editingHoliday = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    name: '',
    date: '',
    description: '',
});

watch(search, debounce((value) => {
    router.get(route('holidays.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const openModal = (holiday = null) => {
    editingHoliday.value = holiday;
    if (holiday) {
        form.name = holiday.name;
        form.date = holiday.date;
        form.description = holiday.description || '';
    } else {
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (editingHoliday.value) {
        form.put(route('holidays.update', editingHoliday.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('holidays.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteHoliday = (id) => {
    if (confirm('Are you sure you want to remove this holiday?')) {
        router.delete(route('holidays.destroy', id), { preserveScroll: true });
    }
};

const closeModal = () => {
    showModal.value = false;
    editingHoliday.value = null;
    form.reset();
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-IN', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};

const getDay = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-IN', { weekday: 'short' });
};

const isDayPast = (dateString) => {
    return new Date(dateString) < new Date();
};
</script>

<template>
    <Head title="Holiday Calendar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6">
                <h2 class="text-2xl font-black leading-tight text-gray-800 uppercase tracking-tighter">
                    Holiday Calendar
                </h2>
                <div class="flex items-center gap-3 ml-auto">
                    <button v-if="canManage" @click="openModal()"
                        class="bg-[#2CA01C] hover:bg-[#238016] text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-xl transition-all flex items-center gap-2 text-[11px] uppercase whitespace-nowrap active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                        Add Holiday
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 px-1">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Stats Row -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(item, i) in [
                        { label: 'Total Holidays', value: holidays.total, color: 'indigo', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
                        { label: 'This Month', value: stats?.this_month ?? 0, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: 'Upcoming', value: stats?.upcoming ?? 0, color: 'amber', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: 'Past Holidays', value: stats?.past ?? 0, color: 'rose', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
                    ]" :key="i"
                        class="bg-white border border-gray-100 rounded-[2.5rem] p-8 shadow-sm flex items-center gap-6 transition-transform hover:scale-[1.02]">
                        <div class="p-4 rounded-3xl" :class="{
                            'bg-indigo-50 text-indigo-600': item.color === 'indigo',
                            'bg-amber-50 text-amber-600': item.color === 'amber',
                            'bg-emerald-50 text-emerald-600': item.color === 'emerald',
                            'bg-rose-50 text-rose-600': item.color === 'rose',
                        }">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="item.icon" /></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ item.label }}</p>
                            <p class="text-3xl font-black text-gray-900 mt-1">{{ item.value }}</p>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    :headers="[
                        { key: 'name', label: 'Holiday Name', sortable: true },
                        { key: 'day', label: 'Day' },
                        { key: 'date', label: 'Date', sortable: true },
                        { key: 'description', label: 'Description' },
                        { key: 'actions', label: 'Actions' }
                    ]"
                    :items="holidays.data"
                    placeholder="Search holidays..."
                    @search="val => search = val"
                >
                    <template #row="{ item: holiday }">
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-2xl flex flex-col items-center justify-center shadow-sm border"
                                    :class="isDayPast(holiday.date) ? 'bg-gray-50 border-gray-100 text-gray-400' : 'bg-indigo-50 border-indigo-100 text-indigo-600'">
                                    <span class="text-sm font-black leading-none">{{ new Date(holiday.date).getDate() }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ holiday.name }}</div>
                                    <div v-if="isDayPast(holiday.date)" class="text-[9px] font-black text-gray-300 uppercase tracking-widest mt-0.5">Past Holiday</div>
                                    <div v-else class="text-[9px] font-black text-emerald-500 uppercase tracking-widest mt-0.5">Upcoming</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <span class="inline-flex px-3 py-1.5 bg-gray-100 text-gray-600 border border-gray-200 rounded-xl text-[9px] font-black uppercase tracking-[0.2em]">
                                {{ getDay(holiday.date) }}
                            </span>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap font-black text-indigo-600 uppercase tracking-widest text-xs">
                            {{ formatDate(holiday.date) }}
                        </td>
                        <td class="px-6 py-6 max-w-[200px]">
                            <p class="text-[10px] text-gray-400 font-bold italic line-clamp-1" :title="holiday.description">
                                {{ holiday.description || '—' }}
                            </p>
                        </td>
                        <td class="px-6 py-6">
                            <div v-if="canManage" class="flex items-center justify-end gap-2">
                                <button @click="openModal(holiday)"
                                    class="bg-gray-50 hover:bg-indigo-600 hover:text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-90 border border-gray-100">
                                    Edit
                                </button>
                                <button @click="deleteHoliday(holiday.id)"
                                    class="bg-rose-50 hover:bg-rose-600 hover:text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-90 border border-rose-100 text-rose-700">
                                    Delete
                                </button>
                            </div>
                            <span v-else class="text-[9px] font-black text-gray-300 uppercase italic tracking-widest">Read Only</span>
                        </td>
                    </template>
                </DataTable>

                <div class="flex justify-end pr-4 mt-4">
                    <Pagination :links="holidays.links" />
                </div>

            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Modal :show="showModal" @close="closeModal" :title="editingHoliday ? 'Edit Holiday' : 'Add Holiday'" maxWidth="lg">
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Holiday Name</label>
                    <input v-model="form.name" type="text"
                        class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5"
                        placeholder="e.g. Diwali, Independence Day" required />
                    <p v-if="form.errors.name" class="text-rose-500 text-[10px] font-black uppercase mt-2 ml-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Date</label>
                    <input v-model="form.date" type="date"
                        class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-3.5"
                        required />
                    <p v-if="form.errors.date" class="text-rose-500 text-[10px] font-black uppercase mt-2 ml-1">{{ form.errors.date }}</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Description (Optional)</label>
                    <textarea v-model="form.description" rows="3"
                        class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner placeholder-gray-300 py-4 resize-none"
                        placeholder="Short note about this holiday..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" @click="closeModal" class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-all">Cancel</button>
                    <button type="submit" :disabled="form.processing"
                        class="px-12 py-3.5 bg-indigo-600 hover:bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-100 active:scale-95">
                        {{ form.processing ? 'Saving...' : (editingHoliday ? 'Update Holiday' : 'Save Holiday') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
