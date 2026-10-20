<x-guest-layout :title="__('Reset password')">

<div class="space-y-2 mb-8">
    <div class="w-12 h-12 bg-violet-600/15 border border-violet-500/25 rounded-2xl flex items-center justify-center mb-4">
        <i class="bi bi-shield-lock text-violet-400 text-xl"></i>
    </div>
    <h2 class="text-2xl font-bold text-white">Set new password</h2>
    <p class="text-slate-400 text-sm">Choose a strong password for your account.</p>
</div>

<form method="POST" action="{{ route('password.store') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="space-y-1.5">
        <label for="email" class="block text-sm font-medium text-slate-300">Email address</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none"><i class="bi bi-envelope text-sm"></i></span>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                class="w-full pl-9 pr-4 py-2.5 bg-slate-800/60 border border-slate-700 hover:border-slate-600 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-white placeholder-slate-500 text-sm outline-none transition @error('email') border-rose-500 @enderror">
        </div>
        @error('email')<p class="flex items-center gap-1.5 text-rose-400 text-xs mt-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <div class="space-y-1.5">
        <label for="password" class="block text-sm font-medium text-slate-300">New password</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none"><i class="bi bi-lock text-sm"></i></span>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Min 8 characters"
                class="w-full pl-9 pr-4 py-2.5 bg-slate-800/60 border border-slate-700 hover:border-slate-600 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-white placeholder-slate-500 text-sm outline-none transition @error('password') border-rose-500 @enderror">
        </div>
        @error('password')<p class="flex items-center gap-1.5 text-rose-400 text-xs mt-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <div class="space-y-1.5">
        <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Confirm new password</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none"><i class="bi bi-lock-fill text-sm"></i></span>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Repeat new password"
                class="w-full pl-9 pr-4 py-2.5 bg-slate-800/60 border border-slate-700 hover:border-slate-600 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-white placeholder-slate-500 text-sm outline-none transition @error('password_confirmation') border-rose-500 @enderror">
        </div>
        @error('password_confirmation')<p class="flex items-center gap-1.5 text-rose-400 text-xs mt-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <button type="submit"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-lg shadow-violet-900/40 transition text-sm">
        <i class="bi bi-check-lg"></i>
        Reset password
    </button>
</form>

</x-guest-layout>
