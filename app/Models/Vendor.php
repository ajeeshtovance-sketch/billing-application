<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'gstin',
        'status',
        'notes',
    ];

    protected $casts = [
        'address' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
