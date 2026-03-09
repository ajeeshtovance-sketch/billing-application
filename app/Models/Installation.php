<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installation extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'lead_id',
        'quotation_id',
        'customer_id',
        'installation_manager_id',
        'installation_number',
        'scheduled_date',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installationManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installation_manager_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InstallationAssignment::class);
    }

    public function technicians(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(User::class, InstallationAssignment::class, 'installation_id', 'id', 'id', 'user_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(InstallationChecklist::class)->orderBy('sort_order');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InstallationPhoto::class);
    }
}
