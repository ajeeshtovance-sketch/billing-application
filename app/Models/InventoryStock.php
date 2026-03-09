<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStock extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'item_id',
        'quantity',
        'low_stock_alert',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'low_stock_alert' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
