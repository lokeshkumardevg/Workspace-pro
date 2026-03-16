<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex font-sans">
        <!-- Left Side: Image/Branding -->
        <div class="hidden lg:flex w-1/2 bg-indigo-900 justify-center items-center relative overflow-hidden flex-col">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-800 to-purple-900 opacity-90"></div>
            
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 -m-20 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute bottom-0 left-0 -m-20 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>

            <div class="relative z-10 p-12 text-white flex flex-col justify-center max-w-lg">
                <Link href="/" class="flex items-center gap-4 mb-12">
                    <img src="/images/logo.svg" alt="Wheedle 360" class="h-12 w-auto object-contain">
                </Link>
                
                <h1 class="text-5xl font-bold leading-tight mb-6">Manage Everything, <br/> Effortlessly.</h1>
                <p class="text-indigo-200 text-lg leading-relaxed font-medium">
                    Elevate your team's productivity with our comprehensive task, project, and lead management system. Gain insights and track performance all in one place.
                </p>
                
                <div class="mt-12 flex items-center space-x-4">
                    <div class="flex -space-x-3">
                        <img class="w-10 h-10 rounded-full border-2 border-indigo-900" src="https://ui-avatars.com/api/?name=Alex&background=E0E7FF&color=3730A3" alt="" />
                        <img class="w-10 h-10 rounded-full border-2 border-indigo-900" src="https://ui-avatars.com/api/?name=Sarah&background=F3E8FF&color=6B21A8" alt="" />
                        <img class="w-10 h-10 rounded-full border-2 border-indigo-900" src="https://ui-avatars.com/api/?name=John&background=DCFCE7&color=166534" alt="" />
                    </div>
                    <p class="text-sm font-medium text-indigo-200">Trusted by over 5,000+ professionals</p>
                </div>
            </div>
            
            <!-- Custom graphic representation -->
            <div class="relative z-10 w-full max-w-md mt-10">
                 <div class="bg-white/10 backdrop-blur-md border border-white/20 p-6 rounded-2xl shadow-2xl">
                    <div class="h-4 w-1/3 bg-white/30 rounded mb-4"></div>
                    <div class="space-y-3">
                        <div class="h-2 w-full bg-white/20 rounded"></div>
                        <div class="h-2 w-5/6 bg-white/20 rounded"></div>
                        <div class="h-2 w-4/6 bg-white/20 rounded"></div>
                    </div>
                 </div>
            </div>
        </div>

        <!-- Right Side: Form Content -->
        <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:flex-none lg:w-1/2 lg:px-20 xl:px-24 bg-white relative shadow-2xl z-20">
            <div class="mx-auto w-full max-w-md">
                <slot />
            </div>
        </div>
    </div>
</template>

<style>
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
</style>
