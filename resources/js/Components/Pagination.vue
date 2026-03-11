<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1 mt-4 justify-center sm:justify-end">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 border rounded-lg" v-html="link.label" />
            <Link v-else class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded-lg hover:bg-indigo-50 focus:border-indigo-500 focus:text-indigo-500 transition-colors" :class="{ 'bg-indigo-600 text-white hover:bg-indigo-700': link.active }" :href="link.url" v-html="link.label" />
        </template>
    </div>
</template>
