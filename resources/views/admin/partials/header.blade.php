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
                <a href="{{ route('admin.pengaturan-pembayaran.edit') }}"
                   class="{{ request()->routeIs('admin.pengaturan-pembayaran.*') ? 'text-rose-600 font-semibold' : 'text-neutral-500 hover:text-rose-600' }}">
                    Pengaturan Pembayaran
                </a>
            </nav>
        </div>
        <div class="flex items-center gap-4 shrink-0">
            <div class="relative">
                <button type="button" id="notif-toggle" data-url="{{ route('admin.notifikasi.index') }}"
                        class="relative p-2 rounded-lg text-neutral-500 hover:text-rose-600 hover:bg-neutral-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span id="notif-badge" class="hidden absolute top-0.5 right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center">0</span>
                </button>
                <div id="notif-panel" class="hidden absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto rounded-xl border border-neutral-200 bg-white shadow-lg z-20">
                    <div class="flex items-center justify-between px-4 py-2.5 border-b border-neutral-100">
                        <p class="text-sm font-bold text-neutral-900">Notifikasi</p>
                        <button type="button" id="notif-baca-semua" class="text-xs font-medium text-rose-600 hover:underline">Tandai semua dibaca</button>
                    </div>
                    <div id="notif-list">
                        <p class="p-4 text-sm text-neutral-400 text-center">Memuat...</p>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-neutral-500 hover:text-rose-600">Keluar</button>
            </form>
        </div>
    </div>
</header>
