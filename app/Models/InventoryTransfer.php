<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransfer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'from_organization_id',
        'to_organization_id',
        'item_id',
        'quantity',
        'status',
        'created_by',
        'received_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function toOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
