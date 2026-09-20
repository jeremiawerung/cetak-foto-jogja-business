function formatRupiah(angka) {
    return 'Rp' + Math.round(angka).toLocaleString('id-ID');
}

export function initCheckoutPengiriman() {
    const opsiDikirim = document.getElementById('opsi-dikirim');
    if (!opsiDikirim) {
        return;
    }

    const urls = window.CHECKOUT_URLS || {};
    const opsiAmbilToko = document.getElementById('opsi-ambil-toko');
    const wrapperAlamat = document.getElementById('wrapper-alamat');

    const inputCari = document.getElementById('input-cari-tujuan');
    const hasilTujuan = document.getElementById('hasil-tujuan');
    const blokCariTujuan = document.getElementById('blok-cari-tujuan');
    const blokTujuanTerpilih = document.getElementById('blok-tujuan-terpilih');
    const tujuanTerpilihLabel = document.getElementById('tujuan-terpilih-label');
    const btnUbahTujuan = document.getElementById('btn-ubah-tujuan');
    const inputTujuanId = document.getElementById('input-tujuan-id');
    const inputTujuanLabel = document.getElementById('input-tujuan-label');

    const wrapperAlamatLengkap = document.getElementById('wrapper-alamat-lengkap');
    const wrapperKurir = document.getElementById('wrapper-kurir');
    const loadingKurir = document.getElementById('loading-kurir');
    const daftarKurir = document.getElementById('daftar-kurir');
    const inputKurirKode = document.getElementById('input-kurir-kode');
    const inputKurirLayanan = document.getElementById('input-kurir-layanan');
    const btnLanjut = document.getElementById('btn-lanjut-konfirmasi');

    let timerCari = null;

    function update() {
        wrapperAlamat.classList.toggle('hidden', !opsiDikirim.checked);
    }

    opsiAmbilToko.addEventListener('change', update);
    opsiDikirim.addEventListener('change', update);
    update();

    function pilihTujuan(tujuan) {
        inputTujuanId.value = tujuan.id;
        inputTujuanLabel.value = tujuan.label;
        tujuanTerpilihLabel.textContent = tujuan.label;

        blokCariTujuan.classList.add('hidden');
        blokTujuanTerpilih.classList.remove('hidden');
        wrapperAlamatLengkap.classList.remove('hidden');
        wrapperKurir.classList.remove('hidden');

        muatOpsiKurir(tujuan.id);
    }

    function resetTujuan() {
        inputTujuanId.value = '';
        inputTujuanLabel.value = '';
        inputKurirKode.value = '';
        inputKurirLayanan.value = '';

        blokTujuanTerpilih.classList.add('hidden');
        blokCariTujuan.classList.remove('hidden');
        wrapperAlamatLengkap.classList.add('hidden');
        wrapperKurir.classList.add('hidden');
        daftarKurir.innerHTML = '';
        inputCari.value = '';
        hasilTujuan.classList.add('hidden');
    }

    btnUbahTujuan.addEventListener('click', resetTujuan);

    inputCari.addEventListener('input', () => {
        clearTimeout(timerCari);
        const keyword = inputCari.value.trim();

        if (keyword.length < 3) {
            hasilTujuan.classList.add('hidden');
            hasilTujuan.innerHTML = '';
            return;
        }

        timerCari = setTimeout(async () => {
            try {
                const response = await fetch(`${urls.cariTujuan}?q=${encodeURIComponent(keyword)}`, {
                    headers: { Accept: 'application/json' },
                });
                const json = await response.json();
                renderHasilTujuan(json.data || []);
            } catch (err) {
                hasilTujuan.innerHTML = '<div class="px-3 py-2 text-sm text-red-600">Gagal memuat, coba lagi.</div>';
                hasilTujuan.classList.remove('hidden');
            }
        }, 400);
    });

    function renderHasilTujuan(data) {
        hasilTujuan.innerHTML = '';

        if (data.length === 0) {
            hasilTujuan.innerHTML = '<div class="px-3 py-2 text-sm text-neutral-400">Tidak ditemukan.</div>';
            hasilTujuan.classList.remove('hidden');
            return;
        }

        data.forEach((tujuan) => {
            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'block w-full text-left px-3 py-2 text-sm hover:bg-rose-50';
            el.textContent = tujuan.label;
            el.addEventListener('click', () => pilihTujuan(tujuan));
            hasilTujuan.appendChild(el);
        });

        hasilTujuan.classList.remove('hidden');
    }

    async function muatOpsiKurir(tujuanId) {
        daftarKurir.innerHTML = '';
        loadingKurir.classList.remove('hidden');
        inputKurirKode.value = '';
        inputKurirLayanan.value = '';

        try {
            const response = await fetch(`${urls.opsiOngkir}?tujuan_id=${encodeURIComponent(tujuanId)}`, {
                headers: { Accept: 'application/json' },
            });
            const json = await response.json();
            renderDaftarKurir(json.data || []);
        } catch (err) {
            daftarKurir.innerHTML = '<p class="text-sm text-red-600">Gagal memuat opsi pengiriman, coba pilih ulang tujuan.</p>';
        } finally {
            loadingKurir.classList.add('hidden');
        }
    }

    function renderDaftarKurir(data) {
        daftarKurir.innerHTML = '';

        if (data.length === 0) {
            daftarKurir.innerHTML = '<p class="text-sm text-neutral-400">Tidak ada layanan pengiriman tersedia ke tujuan ini.</p>';
            return;
        }

        data.forEach((opsi, i) => {
            const label = document.createElement('label');
            label.className = 'flex items-center justify-between gap-3 rounded-lg border border-neutral-200 p-3 text-sm cursor-pointer has-[:checked]:border-rose-400 has-[:checked]:bg-rose-50';

            const kiri = document.createElement('span');
            kiri.className = 'flex items-center gap-3';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'kurir_pilihan';
            radio.value = `${opsi.kode}|${opsi.layanan}`;
            if (i === 0) {
                radio.checked = true;
                inputKurirKode.value = opsi.kode;
                inputKurirLayanan.value = opsi.layanan;
            }
            radio.addEventListener('change', () => {
                inputKurirKode.value = opsi.kode;
                inputKurirLayanan.value = opsi.layanan;
            });

            const teks = document.createElement('span');
            teks.innerHTML = `<span class="block font-medium text-neutral-800">${opsi.nama} - ${opsi.layanan}</span>` +
                `<span class="block text-xs text-neutral-500">${opsi.deskripsi}${opsi.etd ? ' &middot; Estimasi ' + opsi.etd : ''}</span>`;

            kiri.appendChild(radio);
            kiri.appendChild(teks);

            const harga = document.createElement('span');
            harga.className = 'font-semibold text-neutral-800 shrink-0';
            harga.textContent = formatRupiah(opsi.biaya);

            label.appendChild(kiri);
            label.appendChild(harga);
            daftarKurir.appendChild(label);
        });
    }
}

export function initCheckoutKonfirmasi() {
    const modal = document.getElementById('modal-syarat');
    if (!modal) {
        return;
    }

    const btnLihatSyarat = document.getElementById('btn-lihat-syarat');
    const btnSetuju = document.getElementById('btn-setuju-syarat');
    const inputSyarat = document.getElementById('input-syarat');

    function bukaModal() {
        modal.classList.remove('hidden');
    }

    function tutupModal() {
        modal.classList.add('hidden');
    }

    bukaModal();

    btnLihatSyarat.addEventListener('click', bukaModal);
    btnSetuju.addEventListener('click', () => {
        inputSyarat.checked = true;
        tutupModal();
    });

    const opsiBayarToko = document.getElementById('opsi-bayar-toko');
    const opsiTransfer = document.getElementById('opsi-transfer');
    const opsiQris = document.getElementById('opsi-qris');
    const wrapperTransfer = document.getElementById('wrapper-transfer');
    const infoTransfer = document.getElementById('info-transfer');
    const infoQris = document.getElementById('info-qris');
    const labelBukti = document.getElementById('label-bukti');

    if (!opsiTransfer) {
        return;
    }

    function update() {
        const isTransfer = opsiTransfer.checked;
        const isQris = opsiQris.checked;

        wrapperTransfer.classList.toggle('hidden', !isTransfer && !isQris);
        infoTransfer.classList.toggle('hidden', !isTransfer);
        infoQris.classList.toggle('hidden', !isQris);
        labelBukti.textContent = isQris ? 'Upload Bukti Pembayaran QRIS' : 'Upload Bukti Transfer';
    }

    if (opsiBayarToko) {
        opsiBayarToko.addEventListener('change', update);
    }
    opsiTransfer.addEventListener('change', update);
    opsiQris.addEventListener('change', update);
    update();
}
