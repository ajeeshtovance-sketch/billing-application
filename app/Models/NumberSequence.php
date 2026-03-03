<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class NumberSequence extends Model
{
    protected $fillable = [
        'organization_id',
        'sequence_type',
        'prefix',
        'current_value',
        'padding',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function getNext(string $organizationId, string $type, string $prefix = null): string
    {
        return DB::transaction(function () use ($organizationId, $type, $prefix) {
            $seq = self::where('organization_id', $organizationId)
                ->where('sequence_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $seq = self::create([
                    'organization_id' => $organizationId,
                    'sequence_type' => $type,
                    'prefix' => $prefix ?? strtoupper(substr($type, 0, 3)),
                    'current_value' => 1,
                    'padding' => 5,
                ]);

                $next = 1;
            } else {
                $seq->increment('current_value');
                $seq->refresh();
                $next = $seq->current_value;
            }

            $padded = str_pad((string) $next, $seq->padding, '0', STR_PAD_LEFT);

            return ($seq->prefix ? $seq->prefix.'-' : '').$padded;
        });
    }
}
