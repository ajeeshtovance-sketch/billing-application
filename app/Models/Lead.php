<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'lead_number',
        'customer_name',
        'phone',
        'email',
        'address',
        'electricity_bill_amount',
        'location_gps',
        'roof_type',
        'lead_source',
        'assigned_to',
        'status',
        'follow_up_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'address' => 'array',
        'electricity_bill_amount' => 'decimal:2',
        'follow_up_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function survey(): HasOne
    {
        return $this->hasOne(Survey::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
