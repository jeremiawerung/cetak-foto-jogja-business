<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-neutral-200">
    <nav class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/cetak-foto-jogja-logo.png') }}" alt="Cetak Foto Jogja - Online Photo Print Service" width="412" height="140" class="h-9 sm:h-10 w-auto">
            </a>

            <div class="hidden sm:flex items-center gap-1">
                <a href="{{ route('home') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('home') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:text-rose-600' }}">Beranda</a>
                <a href="{{ route('cetak-foto.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('cetak-foto.*') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:text-rose-600' }}">Cetak Foto</a>
                <a href="{{ route('sewa-fotografer.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('sewa-fotografer.*') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:text-rose-600' }}">Sewa Fotografer</a>
                <a href="{{ route('cek-pesanan') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('cek-pesanan') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:text-rose-600' }}">Cek Pesanan</a>
                <a href="{{ route('keranjang.index') }}" class="relative px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('keranjang.*') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:text-rose-600' }}">
                    Keranjang
                    @if (count(session('cart', [])) > 0)
                        <span class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white">{{ count(session('cart', [])) }}</span>
                    @endif
                </a>
            </div>

            <button type="button" id="nav-toggle" class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-neutral-500 hover:bg-neutral-100" aria-label="Buka menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>
        </div>

        <div id="nav-menu" class="hidden sm:hidden pb-4 space-y-1">
            <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('home') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:bg-neutral-50' }}">Beranda</a>
            <a href="{{ route('cetak-foto.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('cetak-foto.*') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:bg-neutral-50' }}">Cetak Foto</a>
            <a href="{{ route('sewa-fotografer.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('sewa-fotografer.*') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:bg-neutral-50' }}">Sewa Fotografer</a>
            <a href="{{ route('cek-pesanan') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('cek-pesanan') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:bg-neutral-50' }}">Cek Pesanan</a>
            <a href="{{ route('keranjang.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('keranjang.*') ? 'text-rose-600 bg-rose-50' : 'text-neutral-600 hover:bg-neutral-50' }}">
                Keranjang ({{ count(session('cart', [])) }})
            </a>
        </div>
    </nav>
</header>
