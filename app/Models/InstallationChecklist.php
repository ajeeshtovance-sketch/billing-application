<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationChecklist extends Model
{
    use SoftDeletes;
    protected $fillable = ['installation_id', 'task_name', 'completed', 'completed_at', 'sort_order'];

    protected $casts = ['completed' => 'boolean', 'completed_at' => 'datetime'];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }
}
