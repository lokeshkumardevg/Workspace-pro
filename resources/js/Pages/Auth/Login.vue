<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="mb-10 text-center lg:text-left">
            <div class="lg:hidden flex justify-center mb-6 mt-6">
                <!-- Mobile Logo -->
                <Link href="/" class="flex items-center gap-2">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-2xl font-bold text-gray-900 tracking-tight">WorkSpace Pro</span>
                </Link>
            </div>
            
            <h2 class="text-3xl font-bold text-gray-900 leading-9">Welcome Back</h2>
            <p class="mt-2 text-sm text-gray-600">Please sign in to your account to continue.</p>
        </div>

        <div v-if="status" class="mb-6 py-3 px-4 border text-sm font-medium text-green-700 bg-green-50 border-green-200 rounded-lg">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-semibold tracking-wide text-gray-700">Email Address <span class="text-red-500">*</span></label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </div>
                    <input
                        id="email"
                        type="email"
                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm transition-colors duration-200 bg-gray-50 focus:bg-white"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="admin@example.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold tracking-wide text-gray-700">Password <span class="text-red-500">*</span></label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        id="password"
                        type="password"
                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm transition-colors duration-200 bg-gray-50 focus:bg-white"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <div class="flex items-center">
                    <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <label for="remember" class="ml-2 block text-sm font-medium text-gray-700 select-none">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="font-semibold text-indigo-600 hover:text-indigo-500 hover:underline transition-colors focus:outline-none"
                    >
                        Forgot password?
                    </Link>
                </div>
            </div>

            <div class="pt-4">
                <button
                    type="submit"
                    class="w-full relative flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all hover:-translate-y-0.5"
                    :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">Sign In</span>
                    <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Signing in...
                    </span>
                </button>
            </div>
            
            <div class="mt-8 text-center text-sm font-medium text-gray-500">
                Don't have an account? 
                <Link :href="route('register')" class="text-indigo-600 hover:text-indigo-500 hover:underline transition-colors font-semibold">
                    Sign up now
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
