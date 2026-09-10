<?php

namespace App\Services;

class WhatsAppLinkBuilder
{
    public function build(string $message): string
    {
        $number = config('services.whatsapp.number');

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
