<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryChallanLineItem extends Model
{
    use SoftDeletes;
    protected $table = 'delivery_challan_line_items';

    protected $fillable = [
        'delivery_challan_id',
        'item_id',
        'description',
        'quantity',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function deliveryChallan(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallan::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
