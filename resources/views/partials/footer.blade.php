<footer class="mt-16 border-t border-neutral-200 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid gap-8 sm:grid-cols-3">
        <div>
            <img src="{{ asset('images/cetak-foto-jogja-logo.png') }}" alt="Cetak Foto Jogja - Online Photo Print Service" width="412" height="140" class="h-8 w-auto">
            <p class="mt-3 text-sm text-neutral-500">Cetak foto & sewa fotografer panggilan di Jogja. Order gampang, tinggal chat WhatsApp.</p>
        </div>

        <div>
            <p class="font-semibold text-sm text-neutral-700">Layanan</p>
            <ul class="mt-2 space-y-1 text-sm text-neutral-500">
                <li><a href="{{ route('cetak-foto.index') }}" class="hover:text-rose-600">Cetak Foto</a></li>
                <li><a href="{{ route('sewa-fotografer.index') }}" class="hover:text-rose-600">Sewa Fotografer</a></li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-sm text-neutral-700">Kontak</p>
            <ul class="mt-2 space-y-1 text-sm text-neutral-500">
                <li>
                    <a href="https://wa.me/{{ config('services.whatsapp.number') }}" target="_blank" rel="noopener" class="hover:text-rose-600">WhatsApp 0895 708 600 900</a>
                </li>
                <li>
                    <a href="https://instagram.com/cetakfotojogja" target="_blank" rel="noopener" class="hover:text-rose-600">Instagram @cetakfotojogja</a>
                </li>
                <li>
                    <a href="https://maps.app.goo.gl/XV2h6SeEgFWF8is39" target="_blank" rel="noopener" class="hover:text-rose-600">
                        Jl. Tempel, Gendol, Margorejo, Kec. Tempel, Kab. Sleman, DIY 55552
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-neutral-100 py-4 text-center text-xs text-neutral-400">
        &copy; {{ date('Y') }} Cetak Foto Jogja. Semua hak cipta dilindungi.
    </div>
</footer>
