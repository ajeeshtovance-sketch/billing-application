<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryChallan extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'dc_number',
        'customer_id',
        'status',
        'delivery_date',
        'shipping_address',
        'notes',
        'invoice_id',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'shipping_address' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(DeliveryChallanLineItem::class);
    }
}
