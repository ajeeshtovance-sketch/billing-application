<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'lead_id',
        'organization_id',
        'engineer_id',
        'roof_type',
        'roof_size_sqft',
        'shadow_analysis',
        'direction',
        'inverter_capacity_recommendation',
        'system_size_kw',
        'load_analysis',
        'electrical_connection_notes',
        'report_url',
        'status',
        'survey_date',
        'completed_at',
    ];

    protected $casts = [
        'roof_size_sqft' => 'decimal:2',
        'system_size_kw' => 'decimal:2',
        'survey_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }
}
