const BULAN_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function toDateKey(year, month, day) {
    const mm = String(month + 1).padStart(2, '0');
    const dd = String(day).padStart(2, '0');
    return `${year}-${mm}-${dd}`;
}

export function initSewaFotografer() {
    const calendarEl = document.getElementById('calendar');
    const form = document.getElementById('booking-form');
    if (!calendarEl || !form) {
        return;
    }

    const availabilityUrl = calendarEl.dataset.availabilityUrl;
    const allSlots = calendarEl.dataset.slots.split(',');

    const calTitle = document.getElementById('cal-title');
    const calGrid = document.getElementById('cal-grid');
    const inputTanggal = document.getElementById('input-tanggal');
    const tanggalTerpilih = document.getElementById('tanggal-terpilih');
    const inputJam = document.getElementById('input-jam');
    const formError = document.getElementById('form-error');
    const btnSubmit = document.getElementById('btn-submit');

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let viewYear = today.getFullYear();
    let viewMonth = today.getMonth();
    let selectedDateKey = null;
    let monthAvailability = {};

    async function fetchMonthAvailability(year, month) {
        const bulan = `${year}-${String(month + 1).padStart(2, '0')}`;
        try {
            const res = await fetch(`${availabilityUrl}?bulan=${bulan}`, {
                headers: { Accept: 'application/json' },
            });
            monthAvailability = res.ok ? await res.json() : {};
        } catch (err) {
            monthAvailability = {};
        }
    }

    function populateJamOptions(dateKey) {
        const bookedTimes = monthAvailability[dateKey] || [];
        inputJam.innerHTML = '';

        if (bookedTimes.length >= allSlots.length) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Semua jam penuh, pilih tanggal lain';
            inputJam.appendChild(opt);
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih jam --';
        inputJam.appendChild(placeholder);

        allSlots.forEach((jam) => {
            const opt = document.createElement('option');
            opt.value = jam;
            const penuh = bookedTimes.includes(jam);
            opt.textContent = penuh ? `${jam} (sudah dibooking)` : jam;
            opt.disabled = penuh;
            inputJam.appendChild(opt);
        });
    }

    function selectDate(year, month, day) {
        const dateKey = toDateKey(year, month, day);
        selectedDateKey = dateKey;
        inputTanggal.value = dateKey;

        const dateObj = new Date(year, month, day);
        tanggalTerpilih.textContent = dateObj.toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        });

        populateJamOptions(dateKey);
        renderCalendar();
    }

    function renderCalendar() {
        calTitle.textContent = `${BULAN_ID[viewMonth]} ${viewYear}`;
        calGrid.innerHTML = '';

        const firstDay = new Date(viewYear, viewMonth, 1).getDay();
        const totalDays = new Date(viewYear, viewMonth + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            calGrid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= totalDays; day++) {
            const dateKey = toDateKey(viewYear, viewMonth, day);
            const dateObj = new Date(viewYear, viewMonth, day);
            const bookedTimes = monthAvailability[dateKey] || [];
            const isPast = dateObj < today;
            const isFull = bookedTimes.length >= allSlots.length;
            const isSelected = dateKey === selectedDateKey;

            const cell = document.createElement('button');
            cell.type = 'button';
            cell.textContent = day;
            cell.className = 'h-9 rounded-lg flex items-center justify-center relative transition';

            if (isPast || isFull) {
                cell.disabled = true;
                cell.className += ' text-neutral-300 cursor-not-allowed';
            } else if (isSelected) {
                cell.className += ' bg-rose-600 text-white font-semibold';
            } else {
                cell.className += ' hover:bg-rose-50 text-neutral-700';
            }

            if (!isPast && !isFull && bookedTimes.length > 0 && !isSelected) {
                const dot = document.createElement('span');
                dot.className = 'absolute bottom-1 h-1.5 w-1.5 rounded-full bg-amber-400';
                cell.appendChild(dot);
            }

            if (!isPast && !isFull) {
                cell.addEventListener('click', () => selectDate(viewYear, viewMonth, day));
            }

            calGrid.appendChild(cell);
        }
    }

    async function loadAndRender() {
        await fetchMonthAvailability(viewYear, viewMonth);
        renderCalendar();
    }

    document.getElementById('cal-prev').addEventListener('click', () => {
        viewMonth -= 1;
        if (viewMonth < 0) {
            viewMonth = 11;
            viewYear -= 1;
        }
        loadAndRender();
    });

    document.getElementById('cal-next').addEventListener('click', () => {
        viewMonth += 1;
        if (viewMonth > 11) {
            viewMonth = 0;
            viewYear += 1;
        }
        loadAndRender();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.classList.add('hidden');

        if (!inputTanggal.value) {
            formError.textContent = 'Silakan pilih tanggal pada kalender.';
            formError.classList.remove('hidden');
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Memproses...';

        try {
            const formData = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]').content;

            const response = await fetch(form.dataset.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                const pesan = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Gagal memproses booking.';
                throw new Error(pesan);
            }

            window.location.href = data.wa_link;
        } catch (err) {
            formError.textContent = err.message || 'Terjadi kesalahan, silakan coba lagi.';
            formError.classList.remove('hidden');
            await loadAndRender();
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Booking via WhatsApp';
        }
    });

    loadAndRender();
}
