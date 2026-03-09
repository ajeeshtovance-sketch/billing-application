<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationAssignment extends Model
{
    use SoftDeletes;
    protected $fillable = ['installation_id', 'user_id', 'role'];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
