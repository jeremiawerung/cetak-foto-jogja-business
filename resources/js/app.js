import './bootstrap';
import { initNav } from './modules/nav';
import { initCetakFoto } from './modules/cetak-foto';
import { initSewaFotografer } from './modules/sewa-fotografer';

document.addEventListener('DOMContentLoaded', () => {
    initNav();
    initCetakFoto();
    initSewaFotografer();
});
