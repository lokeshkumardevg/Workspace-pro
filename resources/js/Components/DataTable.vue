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
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md">
        <!-- Table Toolbar -->
        <div v-if="searchable" class="p-6 bg-white border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative w-full sm:w-96 group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    v-model="search" 
                    @input="onSearch"
                    type="text" 
                    :placeholder="placeholder" 
                    class="block w-full pl-11 pr-4 py-3 bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-medium transition-all"
                />
            </div>
            <slot name="actions"></slot>
        </div>

        <!-- Scrollable Container -->
        <div class="overflow-x-auto relative min-h-[400px]">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th 
                            v-for="header in headers" 
                            :key="header.key"
                            @click="onSort(header.key)"
                            scope="col" 
                            class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]"
                            :class="{ 'cursor-pointer hover:text-indigo-600 transition-colors': header.sortable }"
                        >
                            <div class="flex items-center gap-2">
                                {{ header.label }}
                                <svg v-if="header.sortable" class="w-3 h-3" :class="{ 'text-indigo-600': sortKey === header.key }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="sortKey === header.key && sortOrder === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                </svg>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                        <td v-for="h in headers" :key="h.key" class="px-6 py-6">
                            <div class="h-4 bg-gray-100 rounded-lg w-full"></div>
                        </td>
                    </tr>
                    <template v-else>
                        <tr v-for="(item, index) in items" :key="index" class="hover:bg-indigo-50/30 transition-all duration-200 group">
                            <slot name="row" :item="item"></slot>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td :colspan="headers.length" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="p-4 bg-gray-50 rounded-full mb-4">
                                        <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-black text-gray-400 uppercase tracking-widest">Quantum silence: No records found</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
