<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    headers: Array, // [{ key: 'name', label: 'Name', sortable: true }]
    items: Array,
    loading: Boolean,
    searchable: { type: Boolean, default: true },
    placeholder: { type: String, default: 'Search records...' }
});

const emit = defineEmits(['search', 'sort']);

const search = ref('');
const sortKey = ref('');
const sortOrder = ref('asc');

const onSearch = () => {
    emit('search', search.value);
};

const onSort = (key) => {
    if (!key) return;
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
    }
    emit('sort', { key: sortKey.value, order: sortOrder.value });
};
</script>

<template>
    <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden transition-all hover:shadow-2xl">
        <!-- Table Toolbar -->
        <div v-if="searchable" class="p-8 bg-white border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div class="flex-1 max-w-xl">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-gray-300 group-focus-within:text-indigo-600 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        v-model="search" 
                        @input="onSearch"
                        type="text" 
                        :placeholder="placeholder" 
                        class="block w-full pl-14 pr-6 py-4 bg-gray-50 border-none rounded-3xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black transition-all shadow-inner"
                    />
                </div>
            </div>
            <slot name="actions"></slot>
        </div>

        <!-- Scrollable Container -->
        <div class="overflow-x-auto relative min-h-[300px]">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th 
                            v-for="header in headers" 
                            :key="header.key"
                            @click="onSort(header.key)"
                            scope="col" 
                            class="px-8 py-6 text-left text-[10px] font-black text-gray-900 uppercase tracking-[0.25em]"
                            :class="{ 'cursor-pointer hover:text-indigo-600 transition-colors': header.sortable }"
                        >
                            <div class="flex items-center gap-3">
                                {{ header.label }}
                                <svg v-if="header.sortable" class="w-4 h-4 opacity-30" :class="{ 'text-indigo-600 opacity-100': sortKey === header.key }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="sortKey === header.key && sortOrder === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" />
                                </svg>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                        <td v-for="h in headers" :key="h.key" class="px-8 py-8">
                            <div class="h-5 bg-gray-100 rounded-2xl w-full opacity-50"></div>
                        </td>
                    </tr>
                    <template v-else>
                        <tr v-for="(item, index) in items" :key="index" class="hover:bg-indigo-50/40 transition-all duration-300 group">
                            <slot name="row" :item="item"></slot>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td :colspan="headers.length" class="px-8 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="p-6 bg-gray-50 rounded-[2.5rem] mb-6 shadow-inner border border-gray-100">
                                        <svg class="h-16 w-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest font-sans">No records found</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
