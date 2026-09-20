<?php

namespace App\Notifications;

use App\Models\PrintOrder;
use Illuminate\Notifications\Notification;

class OrderMasukNotification extends Notification
{
    public function __construct(private readonly PrintOrder $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Order cetak foto baru',
            'pesan' => "Order {$this->order->nomor_pesanan} dari {$this->order->nama} baru saja masuk.",
            'url' => route('admin.order-cetak-foto.show', $this->order),
        ];
    }
}
