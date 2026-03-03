<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $fillable = [
        'organization_id',
        'credit_note_number',
        'customer_id',
        'invoice_id',
        'status',
        'total',
        'balance',
        'refund_status',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'balance' => 'decimal:2',
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
        return $this->hasMany(CreditNoteLineItem::class);
    }
}
