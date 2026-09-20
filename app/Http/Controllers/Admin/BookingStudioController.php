<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhotographerBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingStudioController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $bookings = PhotographerBooking::when($status && array_key_exists($status, PhotographerBooking::STATUSES), fn ($query) => $query->where('status', $status))
            ->orderByDesc('tanggal')
            ->paginate(20)
            ->withQueryString();

        return view('admin.booking-studio.index', [
            'bookings' => $bookings,
            'statusTerpilih' => $status,
        ]);
    }

    public function show(PhotographerBooking $photographerBooking): View
    {
        return view('admin.booking-studio.show', compact('photographerBooking'));
    }

    public function updateStatus(Request $request, PhotographerBooking $photographerBooking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PhotographerBooking::STATUSES))],
        ]);

        $photographerBooking->update($validated);

        return back()->with('status', 'Status booking berhasil diperbarui.');
    }
}
