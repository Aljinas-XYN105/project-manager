<x-guest-layout :title="__('Sign in')">

<div class="space-y-2 mb-8">
    <h2 class="text-2xl font-bold text-white">Welcome back</h2>
    <p class="text-slate-400 text-sm">Sign in to your Project Manager workspace</p>
</div>

{{-- Session status (e.g. password reset success) --}}
@if (session('status'))
<div class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-500/10 border border-emerald-500/25 rounded-xl text-emerald-400 text-sm">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <span>{{ session('status') }}</span>
</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    {{-- Email --}}
    <div class="space-y-1.5">
        <label for="email" class="block text-sm font-medium text-slate-300">Email address</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                <i class="bi bi-envelope text-sm"></i>
            </span>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="you@company.com"
                class="w-full pl-9 pr-4 py-2.5 bg-slate-800/60 border border-slate-700 hover:border-slate-600 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-white placeholder-slate-500 text-sm outline-none transition @error('email') border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 @enderror"
            >
        </div>
        @error('email')
        <p class="flex items-center gap-1.5 text-rose-400 text-xs">
            <i class="bi bi-exclamation-circle"></i>{{ $message }}
        </p>
        @enderror
    </div>

    {{-- Password --}}
    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <label for="password" class="block text-sm font-medium text-slate-300">Password</label>
            @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-xs text-violet-400 hover:text-violet-300 transition">
                Forgot password?
            </a>
            @endif
        </div>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                <i class="bi bi-lock text-sm"></i>
            </span>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full pl-9 pr-10 py-2.5 bg-slate-800/60 border border-slate-700 hover:border-slate-600 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-white placeholder-slate-500 text-sm outline-none transition @error('password') border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 @enderror"
            >
            <button
                type="button"
                onclick="const f=document.getElementById('password');f.type=f.type==='password'?'text':'password';this.querySelector('i').className=f.type==='password'?'bi bi-eye text-sm':'bi bi-eye-slash text-sm'"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition"
                tabindex="-1"
            >
                <i class="bi bi-eye text-sm"></i>
            </button>
        </div>
        @error('password')
        <p class="flex items-center gap-1.5 text-rose-400 text-xs">
            <i class="bi bi-exclamation-circle"></i>{{ $message }}
        </p>
        @enderror
    </div>

    {{-- Remember me --}}
    <label class="flex items-center gap-2.5 cursor-pointer select-none group">
        <div class="relative">
            <input id="remember_me" type="checkbox" name="remember" class="sr-only peer">
            <div class="w-4 h-4 rounded border border-slate-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition flex-shrink-0"></div>
            <i class="bi bi-check text-white text-xs absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition pointer-events-none"></i>
        </div>
        <span class="text-sm text-slate-400 group-hover:text-slate-300 transition">Remember me for 30 days</span>
    </label>

    {{-- Submit --}}
    <button
        type="submit"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-lg shadow-violet-900/40 transition text-sm"
    >
        <i class="bi bi-box-arrow-in-right"></i>
        Sign in
    </button>
</form>

@if (Route::has('register'))
<p class="mt-6 text-center text-sm text-slate-500">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-violet-400 hover:text-violet-300 font-medium transition">Create one</a>
</p>
@endif

</x-guest-layout>
