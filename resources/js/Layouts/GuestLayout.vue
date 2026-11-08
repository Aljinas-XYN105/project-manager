<script setup>
defineProps({ title: { type: String, default: 'Sign in' } })

const features = ['Kanban', 'Time Tracking', 'People & HR', 'Bug Tracker', 'Leave Mgmt', 'Reports']
const year = new Date().getFullYear()
</script>

<template>
    <div class="font-sans antialiased min-h-screen flex bg-white">

        <!-- Left panel - branding (desktop only) -->
        <div class="hidden lg:flex lg:w-[45%] xl:w-[48%] relative overflow-hidden flex-col p-14"
             style="background: linear-gradient(160deg, #1e1b4b 0%, #2d2572 50%, #1a1745 100%);">

            <!-- Radial glows -->
            <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse 50% 40% at 90% 15%, rgba(139,92,246,0.22) 0%, transparent 65%), radial-gradient(ellipse 40% 50% at 10% 85%, rgba(99,102,241,0.18) 0%, transparent 65%);"></div>

            <!-- Dot grid overlay -->
            <div class="absolute inset-0 opacity-50 pointer-events-none select-none"
                 style="background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 24px 24px;"></div>

            <!-- Logo -->
            <div class="relative z-10 mb-12">
                <img src="/images/workzen-logo.png" alt="Workzen"
                     class="h-12 w-auto" style="filter: brightness(0) invert(1); max-width: 200px;">
            </div>

            <!-- Hero -->
            <div class="relative z-10 flex-1 flex flex-col justify-center space-y-6">
                <div class="space-y-4">
                    <!-- Badge -->
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-semibold tracking-widest uppercase"
                          style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.18); color:rgba(255,255,255,0.7);">
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-300 animate-pulse"></span>
                        Work Smart. Stay in Sync.
                    </span>

                    <!-- Headline -->
                    <div>
                        <h1 class="text-4xl xl:text-5xl font-black text-white leading-[1.1] tracking-tight">
                            Everything<br>
                            <span style="color: transparent; background: linear-gradient(135deg, #a78bfa, #818cf8, #c4b5fd); -webkit-background-clip: text; background-clip: text;">
                                your team
                            </span><br>
                            needs.
                        </h1>
                    </div>

                    <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.5); max-width: 280px;">
                        Sprints, tasks, time tracking, standups, leave, and people — all in one workspace.
                    </p>
                </div>

                <!-- Feature pills -->
                <div class="flex flex-wrap gap-2">
                    <span v-for="feat in features" :key="feat"
                          class="text-[11px] font-medium px-2.5 py-1 rounded-full"
                          style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.13); color:rgba(255,255,255,0.6);">
                        {{ feat }}
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <p class="relative z-10 text-xs" style="color:rgba(255,255,255,0.25);">
                &copy; {{ year }} Workzen &middot; Built for teams
            </p>
        </div>

        <!-- Right panel - form (white) -->
        <div class="flex-1 flex flex-col bg-white min-h-screen lg:min-h-0">

            <!-- Mobile layout -->
            <div class="lg:hidden flex flex-col min-h-screen">
                <div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
                    <div class="mb-8 text-center">
                        <img src="/images/workzen-logo.png" alt="Workzen" class="h-9 w-auto mx-auto">
                    </div>
                    <div class="w-full" style="max-width: 380px;">
                        <slot />
                    </div>
                </div>
                <div class="pb-8 text-center">
                    <p class="text-xs text-slate-400">
                        &copy; {{ year }} Workzen &mdash; Secure connection
                        <i class="bi bi-shield-check ml-1 text-emerald-500"></i>
                    </p>
                </div>
            </div>

            <!-- Desktop layout -->
            <div class="hidden lg:flex flex-1 flex-col">
                <div class="flex-1 flex items-center justify-center p-16">
                    <div class="w-full" style="max-width: 400px;">
                        <slot />
                    </div>
                </div>
                <div class="pb-8 text-center">
                    <p class="text-xs text-slate-400">
                        &copy; {{ year }} Workzen &mdash; Secure connection
                        <i class="bi bi-shield-check ml-1 text-emerald-500"></i>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
