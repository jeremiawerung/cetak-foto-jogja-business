import './bootstrap';
import { initNav } from './modules/nav';
import { initProdukDetail } from './modules/produk-detail';
import { initCheckoutPengiriman, initCheckoutKonfirmasi } from './modules/checkout';
import { initSewaFotografer } from './modules/sewa-fotografer';

document.addEventListener('DOMContentLoaded', () => {
    initNav();
    initProdukDetail();
    initCheckoutPengiriman();
    initCheckoutKonfirmasi();
    initSewaFotografer();
});
