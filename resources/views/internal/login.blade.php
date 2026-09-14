@extends('layouts.internal')

@section('title', 'Login Internal - Cetak Foto Jogja')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm rounded-2xl border border-neutral-200 bg-white p-6 sm:p-8 shadow-sm">
        <h1 class="text-lg font-bold text-neutral-900">Login Internal</h1>
        <p class="mt-1 text-sm text-neutral-500">Akses terbatas untuk tim internal.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('internal.login.attempt') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-neutral-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Password</label>
                <input type="password" name="password" required
                       class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
            </div>

            <label class="flex items-center gap-2 text-sm text-neutral-600">
                <input type="checkbox" name="remember" class="rounded border-neutral-300 text-rose-600 focus:ring-rose-500">
                Ingat saya
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition">
                Masuk
            </button>
        </form>
    </div>
</div>
@endsection
