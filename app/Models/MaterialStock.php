<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'material_id', 'opening_qty', 'received_qty', 'issued_qty',
    'used_qty', 'returned_qty', 'current_qty',
])]
class MaterialStock extends Model
{
    protected $table = 'material_stock';

    protected function casts(): array
    {
        return [
            'opening_qty' => 'decimal:2',
            'received_qty' => 'decimal:2',
            'issued_qty' => 'decimal:2',
            'used_qty' => 'decimal:2',
            'returned_qty' => 'decimal:2',
            'current_qty' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
