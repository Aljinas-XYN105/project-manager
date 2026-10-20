<x-guest-layout :title="__('Forgot password')">

<div class="space-y-2 mb-8">
    <div class="w-12 h-12 bg-violet-600/15 border border-violet-500/25 rounded-2xl flex items-center justify-center mb-4">
        <i class="bi bi-key text-violet-400 text-xl"></i>
    </div>
    <h2 class="text-2xl font-bold text-white">Forgot password?</h2>
    <p class="text-slate-400 text-sm">Enter your email and we'll send a reset link.</p>
</div>

@if (session('status'))
<div class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-500/10 border border-emerald-500/25 rounded-xl text-emerald-400 text-sm">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <span>{{ session('status') }}</span>
</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-5">
    @csrf

    <div class="space-y-1.5">
        <label for="email" class="block text-sm font-medium text-slate-300">Email address</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none"><i class="bi bi-envelope text-sm"></i></span>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="you@company.com"
                class="w-full pl-9 pr-4 py-2.5 bg-slate-800/60 border border-slate-700 hover:border-slate-600 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-white placeholder-slate-500 text-sm outline-none transition @error('email') border-rose-500 @enderror">
        </div>
        @error('email')<p class="flex items-center gap-1.5 text-rose-400 text-xs mt-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <button type="submit"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-lg shadow-violet-900/40 transition text-sm">
        <i class="bi bi-send"></i>
        Send reset link
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-500">
    <a href="{{ route('login') }}" class="text-violet-400 hover:text-violet-300 font-medium transition">
        <i class="bi bi-arrow-left text-xs mr-1"></i>Back to sign in
    </a>
</p>

</x-guest-layout>
