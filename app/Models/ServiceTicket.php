<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTicket extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'installation_id',
        'customer_id',
        'assigned_to',
        'ticket_number',
        'status',
        'priority',
        'complaint',
        'resolution',
        'resolved_at',
        'scheduled_date',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'scheduled_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
