<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionConfig extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'value',
        'applicable_to',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
