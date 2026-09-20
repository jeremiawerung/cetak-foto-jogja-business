@php
    $langkah = ['Produk', 'Informasi', 'Pengiriman', 'Konfirmasi'];
@endphp
<ol class="flex items-center">
    @foreach ($langkah as $i => $label)
        @php $n = $i + 1; @endphp
        <li class="flex items-center {{ $n < count($langkah) ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center gap-1 shrink-0">
                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold border-2
                    {{ $n <= $stepAktif ? 'border-rose-600 bg-rose-600 text-white' : 'border-neutral-300 text-neutral-400' }}">
                    {{ $n }}
                </span>
                <span class="text-xs font-medium {{ $n <= $stepAktif ? 'text-rose-600' : 'text-neutral-400' }}">{{ $label }}</span>
            </div>
            @if ($n < count($langkah))
                <span class="flex-1 h-0.5 mx-2 mb-4 {{ $n < $stepAktif ? 'bg-rose-600' : 'bg-neutral-200' }}"></span>
            @endif
        </li>
    @endforeach
</ol>
