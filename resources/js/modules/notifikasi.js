export function initNotifikasi() {
    const tombol = document.getElementById('notif-toggle');
    const panel = document.getElementById('notif-panel');
    const badge = document.getElementById('notif-badge');
    const daftar = document.getElementById('notif-list');
    const tombolBacaSemua = document.getElementById('notif-baca-semua');

    if (!tombol || !panel || !badge || !daftar) {
        return;
    }

    const url = tombol.dataset.url;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let jumlahSebelumnya = null;

    function mainkanBunyi() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);

            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.4);
        } catch (e) {
            // Browser tidak mendukung/mengizinkan Web Audio API - badge tetap update tanpa suara.
        }
    }

    function render(data) {
        badge.textContent = data.belum_dibaca;
        badge.classList.toggle('hidden', data.belum_dibaca === 0);

        if (data.notifikasi.length === 0) {
            daftar.innerHTML = '<p class="p-4 text-sm text-neutral-400 text-center">Belum ada notifikasi.</p>';
            return;
        }

        daftar.innerHTML = data.notifikasi.map((item) => `
            <a href="${item.url ?? '#'}" data-id="${item.id}"
               class="notif-item block px-4 py-3 border-b border-neutral-100 last:border-b-0 hover:bg-neutral-50 ${item.dibaca ? '' : 'bg-rose-50'}">
                <p class="text-sm font-semibold text-neutral-900">${item.judul}</p>
                <p class="text-xs text-neutral-500 mt-0.5">${item.pesan}</p>
                <p class="text-[11px] text-neutral-400 mt-1">${item.waktu}</p>
            </a>
        `).join('');

        daftar.querySelectorAll('.notif-item').forEach((el) => {
            el.addEventListener('click', () => {
                fetch(`${url}/${el.dataset.id}/baca`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                });
            });
        });
    }

    async function ambilNotifikasi() {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (jumlahSebelumnya !== null && data.belum_dibaca > jumlahSebelumnya) {
                mainkanBunyi();
            }
            jumlahSebelumnya = data.belum_dibaca;

            render(data);
        } catch (e) {
            // Koneksi terputus sesaat - coba lagi di polling berikutnya.
        }
    }

    tombol.addEventListener('click', () => {
        panel.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && !tombol.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });

    tombolBacaSemua?.addEventListener('click', async () => {
        await fetch(`${url}/baca-semua`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        });
        ambilNotifikasi();
    });

    ambilNotifikasi();
    setInterval(ambilNotifikasi, 15000);
}
