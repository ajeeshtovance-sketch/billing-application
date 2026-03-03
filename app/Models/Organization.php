<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'status',
        'user_limit',
        'tax_id',
        'address',
        'billing_address',
        'billing_email',
        'phone',
        'logo_url',
        'base_currency',
        'settings',
    ];

    protected $casts = [
        'address' => 'array',
        'billing_address' => 'array',
        'settings' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
