function formatRupiah(angka) {
    return 'Rp' + Math.round(angka).toLocaleString('id-ID');
}

export function initProdukDetail() {
    const inputJumlah = document.getElementById('input-jumlah');
    if (!inputJumlah) {
        return;
    }

    const produk = window.PRODUK || {};
    const btnKurang = document.getElementById('btn-kurang');
    const btnTambah = document.getElementById('btn-tambah');
    const hargaTampil = document.getElementById('harga-tampil');
    const subtotalTampil = document.getElementById('subtotal-tampil');

    function update() {
        const jumlah = parseInt(inputJumlah.value, 10) || 0;

        if (produk.custom) {
            return;
        }

        if (produk.pricing_mode === 'tiered' && produk.tiers) {
            const tier = produk.tiers.find((t) => jumlah >= t.min && (t.max === null || jumlah <= t.max));
            if (tier && hargaTampil) {
                hargaTampil.textContent = formatRupiah(tier.harga);
            }
        } else if (subtotalTampil) {
            subtotalTampil.textContent = formatRupiah((produk.harga || 0) * jumlah);
        }
    }

    btnKurang.addEventListener('click', () => {
        inputJumlah.value = Math.max(1, (parseInt(inputJumlah.value, 10) || 1) - 1);
        update();
    });

    btnTambah.addEventListener('click', () => {
        inputJumlah.value = (parseInt(inputJumlah.value, 10) || 0) + 1;
        update();
    });

    inputJumlah.addEventListener('input', update);
}
