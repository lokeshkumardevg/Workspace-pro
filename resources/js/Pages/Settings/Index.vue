<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object
});

const form = useForm({
    settings: {
        app_name: props.settings.app_name || 'Antigravity Workspace',
        company_name: props.settings.company_name || 'Antigravity Solutions',
        contact_email: props.settings.contact_email || 'admin@antigravity.com',
        timezone: props.settings.timezone || 'Asia/Kolkata',
        currency: props.settings.currency || 'INR',
        primary_color: props.settings.primary_color || '#6366f1',
        footer_text: props.settings.footer_text || '© 2026 High Performance Management System',
        
        // Geofencing Protocol
        office_lat: props.settings.office_lat || '28.61314773529335',
        office_lng: props.settings.office_lng || '77.38732458230429',
        office_radius: props.settings.office_radius || '200',
    }
});

const isCapturing = ref(false);

const captureLocation = () => {
    if (!navigator.geolocation) {
        alert("Geolocation not supported by browser.");
        return;
    }
    
    isCapturing.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.settings.office_lat = pos.coords.latitude.toString();
            form.settings.office_lng = pos.coords.longitude.toString();
            isCapturing.value = false;
        },
        (err) => {
            alert("Error capturing location: " + err.message);
            isCapturing.value = false;
        },
        { enableHighAccuracy: true }
    );
};

const saveSettings = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional notifications can be handled via global flash props
        }
    });
};
</script>

<template>
    <Head title="System Configuration & Deployment" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between md:items-center w-full gap-6">
                <div>
                    <h2 class="text-3xl font-black leading-tight text-gray-900 uppercase tracking-tighter">
                        Command <span class="text-indigo-600">Center</span> Configuration
                    </h2>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mt-1">Deploy Global System Parameters & High-Level Branding</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl border border-emerald-100 flex items-center gap-2">
                         <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                         <span class="text-[10px] font-black uppercase tracking-widest">Operational Ready</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <form @submit.prevent="saveSettings" class="space-y-8">
                    <!-- Branding Logic Card -->
                    <div class="bg-white border-2 border-gray-50 rounded-[2.5rem] shadow-sm overflow-hidden">
                        <div class="px-10 py-8 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a1 1 0 011 1v12a3 3 0 003 3h7a2 2 0 012 2v1a2 2 0 01-2 2H7z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Enterprise Branding</h3>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Define how the system identifies itself across all protocols</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Application Identity (Label)</label>
                                <input v-model="form.settings.app_name" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Parent Organization</label>
                                <input v-model="form.settings.company_name" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Support/Admin Node (Email)</label>
                                <input v-model="form.settings.contact_email" type="email" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Primary UI Signal (Color)</label>
                                <div class="flex items-center gap-3">
                                    <input v-model="form.settings.primary_color" type="color" class="h-12 w-12 rounded-xl border-0 p-0 cursor-pointer shadow-sm" />
                                    <input v-model="form.settings.primary_color" type="text" class="flex-1 bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Localization Card -->
                    <div class="bg-white border-2 border-gray-50 rounded-[2.5rem] shadow-sm overflow-hidden">
                        <div class="px-10 py-8 border-b border-gray-50 bg-gray-50/30 flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center text-emerald-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Clock & Finance Protocol</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Coordinate system time and currency for global distribution</p>
                            </div>
                        </div>
                        <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Standard Timezone</label>
                                <select v-model="form.settings.timezone" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all">
                                    <option value="UTC">Universal Standard (UTC)</option>
                                    <option value="Asia/Kolkata">Indian Standard (IST)</option>
                                    <option value="America/New_York">Eastern Standard (EST)</option>
                                    <option value="Europe/London">Greenwich (GMT)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Exchange Currency (ISO)</label>
                                <input v-model="form.settings.currency" type="text" placeholder="INR, USD, EUR..." class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all uppercase" />
                            </div>
                        </div>
                    </div>

                    <!-- Geofencing Intelligence -->
                    <div class="bg-white border-2 border-gray-50 rounded-[2.5rem] shadow-sm overflow-hidden">
                        <div class="px-10 py-8 border-b border-gray-50 bg-gray-50/30 flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center text-rose-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Geofencing Protocol</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Calibrate office coordinates and allowed attendance radius</p>
                            </div>
                            <button type="button" @click="captureLocation" :disabled="isCapturing" class="text-[10px] font-black uppercase text-indigo-600 border border-indigo-100 px-4 py-2 rounded-xl hover:bg-indigo-50 transition-all flex items-center gap-2">
                                <svg v-if="isCapturing" class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                🎯 {{ isCapturing ? 'Locating...' : 'Capture My Current Location' }}
                            </button>
                        </div>
                        <div class="p-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Latitude Axis</label>
                                <input v-model="form.settings.office_lat" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-rose-50 focus:border-rose-500 text-sm font-black shadow-inner py-4 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Longitude Axis</label>
                                <input v-model="form.settings.office_lng" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-rose-50 focus:border-rose-500 text-sm font-black shadow-inner py-4 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Safe Radius (Meters)</label>
                                <input v-model="form.settings.office_radius" type="number" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-rose-50 focus:border-rose-500 text-sm font-black shadow-inner py-4 transition-all" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer & Legal -->
                    <div class="bg-white border-2 border-gray-50 rounded-[2.5rem] shadow-sm overflow-hidden">
                        <div class="px-10 py-8 border-b border-gray-50 bg-gray-50/30">
                            <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Legal & Identification</h3>
                        </div>
                        <div class="p-10">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Public Footer Authority Text</label>
                            <input v-model="form.settings.footer_text" type="text" class="w-full bg-gray-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 text-sm font-black shadow-inner py-4 transition-all" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-6 pt-4">
                        <button type="button" class="text-[10px] font-black uppercase text-gray-400 tracking-widest hover:text-gray-900 transition-colors">Factory Reset</button>
                        <button type="submit" :disabled="form.processing" class="bg-gray-900 hover:bg-indigo-600 text-white px-16 py-4 rounded-3xl font-black uppercase text-xs tracking-widest shadow-2xl shadow-indigo-100 transition-all active:scale-95 disabled:opacity-50">
                            {{ form.processing ? 'Deploying...' : 'Deploy Configurations' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
