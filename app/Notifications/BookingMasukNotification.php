<?php

namespace App\Notifications;

use App\Models\PhotographerBooking;
use Illuminate\Notifications\Notification;

class BookingMasukNotification extends Notification
{
    public function __construct(private readonly PhotographerBooking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Booking studio baru',
            'pesan' => "Booking dari {$this->booking->nama} untuk tanggal {$this->booking->tanggal->format('d M Y')} baru saja masuk.",
            'url' => route('admin.booking-studio.show', $this->booking),
        ];
    }
}
