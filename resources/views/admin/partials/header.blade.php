<header class="bg-white border-b border-neutral-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-5 overflow-x-auto">
            <a href="{{ route('admin.dashboard') }}" class="font-bold text-neutral-900 shrink-0">Admin Katalog</a>
            <nav class="flex items-center gap-4 text-sm shrink-0">
                <a href="{{ route('admin.kategori-produk.index') }}"
                   class="{{ request()->routeIs('admin.kategori-produk.*') ? 'text-rose-600 font-semibold' : 'text-neutral-500 hover:text-rose-600' }}">
                    Katalog
                </a>
                <a href="{{ route('admin.order-cetak-foto.index') }}"
                   class="{{ request()->routeIs('admin.order-cetak-foto.*') ? 'text-rose-600 font-semibold' : 'text-neutral-500 hover:text-rose-600' }}">
                    Order Cetak Foto
                </a>
                <a href="{{ route('admin.booking-studio.index') }}"
                   class="{{ request()->routeIs('admin.booking-studio.*') ? 'text-rose-600 font-semibold' : 'text-neutral-500 hover:text-rose-600' }}">
                    Booking Studio
                </a>
            </nav>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="text-sm font-medium text-neutral-500 hover:text-rose-600">Keluar</button>
        </form>
    </div>
</header>
