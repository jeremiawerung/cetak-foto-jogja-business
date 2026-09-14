<header class="bg-white border-b border-neutral-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
        <a href="{{ route('internal.dashboard') }}" class="font-bold text-neutral-900">Internal</a>
        <form method="POST" action="{{ route('internal.logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-neutral-500 hover:text-rose-600">Keluar</button>
        </form>
    </div>
</header>
