<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'category_id',
        'name',
        'sku',
        'barcode',
        'item_type',
        'product_type',
        'description',
        'price',
        'cost',
        'stock_quantity',
        'low_stock_alert',
        'unit',
        'tax_rate',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'low_stock_alert' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
