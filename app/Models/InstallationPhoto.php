<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationPhoto extends Model
{
    use SoftDeletes;
    protected $fillable = ['installation_id', 'file_path', 'type', 'caption', 'uploaded_by'];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
