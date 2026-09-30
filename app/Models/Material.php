<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'code', 'category', 'unit', 'minimum_stock', 'current_stock', 'status'])]
class Material extends Model
{
    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:2',
            'current_stock' => 'decimal:2',
        ];
    }

    public function stock(): HasOne
    {
        return $this->hasOne(MaterialStock::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class);
    }

    public function isLow(): bool
    {
        return (float) $this->current_stock <= (float) $this->minimum_stock;
    }
}
