<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'billing_address',
        'gstin',
        'payment_terms',
        'notes',
        'status',
    ];

    protected $casts = [
        'address' => 'array',
        'billing_address' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
