function formatRupiah(angka) {
    return 'Rp' + Math.round(angka).toLocaleString('id-ID');
}

function hitungEstimasi(catalog, kategoriId, varianId, jumlah) {
    const kategori = catalog[kategoriId];
    if (!kategori || !jumlah || jumlah < 1) {
        return { total: 0, custom: false };
    }

    if (kategori.pricing_mode === 'tiered') {
        const tier = kategori.tiers.find((t) => jumlah >= t.min && (t.max === null || jumlah <= t.max));
        const harga = tier ? tier.harga : 0;
        return { total: harga * jumlah, custom: false };
    }

    const item = (kategori.items || []).find((i) => i.id === varianId);
    if (!item) {
        return { total: 0, custom: false };
    }

    if (item.custom) {
        return { total: 0, custom: true };
    }

    return { total: item.harga * jumlah, custom: false };
}

export function initCetakFoto() {
    const form = document.getElementById('order-form');
    if (!form) {
        return;
    }

    const catalog = window.CATALOG || {};
    const inputKategori = document.getElementById('input-kategori');
    const inputVarian = document.getElementById('input-varian');
    const wrapperVarian = document.getElementById('wrapper-varian');
    const varianNote = document.getElementById('varian-note');
    const labelJumlah = document.getElementById('label-jumlah');
    const inputJumlah = document.getElementById('input-jumlah');
    const estimasiEl = document.getElementById('estimasi-harga');
    const formError = document.getElementById('form-error');
    const btnSubmit = document.getElementById('btn-submit');

    function populateVarian(kategoriId) {
        const kategori = catalog[kategoriId];
        inputVarian.innerHTML = '';

        if (!kategori || kategori.pricing_mode === 'tiered') {
            wrapperVarian.classList.add('hidden');
            varianNote.textContent = kategori && kategori.deskripsi ? kategori.deskripsi : '';
            labelJumlah.textContent = kategori ? `Jumlah (${kategori.satuan_label})` : 'Jumlah';
            return;
        }

        wrapperVarian.classList.remove('hidden');
        labelJumlah.textContent = `Jumlah (${kategori.satuan_label})`;

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih varian --';
        inputVarian.appendChild(placeholder);

        kategori.items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.custom
                ? `${item.nama} (Hubungi Admin)`
                : `${item.nama} - Rp${Math.round(item.harga).toLocaleString('id-ID')}`;
            inputVarian.appendChild(opt);
        });

        varianNote.textContent = '';
    }

    function updateEstimasi() {
        const kategoriId = inputKategori.value;
        const varianId = inputVarian.value;
        const jumlah = parseInt(inputJumlah.value, 10) || 0;

        const hasil = hitungEstimasi(catalog, kategoriId, varianId, jumlah);

        if (hasil.custom) {
            estimasiEl.textContent = 'Hubungi Admin';
        } else {
            estimasiEl.textContent = formatRupiah(hasil.total);
        }
    }

    inputKategori.addEventListener('change', () => {
        populateVarian(inputKategori.value);
        updateEstimasi();
    });

    inputVarian.addEventListener('change', updateEstimasi);
    inputJumlah.addEventListener('input', updateEstimasi);

    document.querySelectorAll('.btn-pesan').forEach((btn) => {
        btn.addEventListener('click', () => {
            const kategoriId = btn.dataset.kategori;
            inputKategori.value = kategoriId;
            populateVarian(kategoriId);
            updateEstimasi();
            document.getElementById('form-order').scrollIntoView({ behavior: 'smooth' });
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.classList.add('hidden');
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Memproses...';

        try {
            const formData = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]').content;

            const response = await fetch(form.dataset.action || '/cetak-foto/order', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                const pesan = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Gagal memproses order.';
                throw new Error(pesan);
            }

            window.location.href = data.wa_link;
        } catch (err) {
            formError.textContent = err.message || 'Terjadi kesalahan, silakan coba lagi.';
            formError.classList.remove('hidden');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Order via WhatsApp';
        }
    });
}
