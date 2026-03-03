<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'organization_id',
        'quotation_number',
        'customer_id',
        'status',
        'valid_until',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'terms',
        'invoice_id',
        'created_by',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
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
        return $this->hasMany(QuotationLineItem::class);
    }
}
