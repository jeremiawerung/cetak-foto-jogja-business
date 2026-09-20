<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintOrderItem extends Model
{
    protected $fillable = [
        'print_order_id',
        'kategori',
        'kategori_label',
        'varian',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'berat_satuan',
        'berat_subtotal',
        'is_custom',
        'file_paths',
        'gdrive_link',
    ];

    protected $casts = [
        'file_paths' => 'array',
        'is_custom' => 'boolean',
    ];

    public function printOrder(): BelongsTo
    {
        return $this->belongsTo(PrintOrder::class);
    }
}
