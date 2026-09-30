<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'material_id', 'project_id', 'project_location_id', 'type', 'quantity',
    'transacted_on', 'remarks', 'user_id', 'reference_type', 'reference_id',
])]
class MaterialTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'transacted_on' => 'date',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return Options::label(Options::STOCK_TYPES, $this->type);
    }
}
