<x-guest-layout :title="__('Create account')">

<div class="space-y-1 mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Create your account</h2>
    <p class="text-slate-500 text-sm">Join your team on Workzen</p>
</div>

<form method="POST" action="{{ route('register') }}" class="space-y-5">
    @csrf

    <div class="space-y-1.5">
        <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-person text-sm"></i></span>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Jane Smith"
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition @error('name') border-rose-400 @enderror">
        </div>
        @error('name')<p class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <div class="space-y-1.5">
        <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-envelope text-sm"></i></span>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@company.com"
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition @error('email') border-rose-400 @enderror">
        </div>
        @error('email')<p class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <div class="space-y-1.5">
        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-lock text-sm"></i></span>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Min 8 characters"
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition @error('password') border-rose-400 @enderror">
        </div>
        @error('password')<p class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <div class="space-y-1.5">
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-lock-fill text-sm"></i></span>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Repeat password"
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition @error('password_confirmation') border-rose-400 @enderror">
        </div>
        @error('password_confirmation')<p class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
    </div>

    <button type="submit"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-md shadow-violet-200 transition text-sm">
        <i class="bi bi-person-plus-fill"></i>
        Create account
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-500">
    Already have an account?
    <a href="{{ route('login') }}" class="text-violet-600 hover:text-violet-500 font-medium transition">Sign in</a>
</p>

</x-guest-layout>
