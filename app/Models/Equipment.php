<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'code', 'equipment_type', 'registration_number', 'owner',
    'operator_name', 'project_id', 'project_location_id', 'status',
])]
class Equipment extends Model
{
    protected $table = 'equipment';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(EquipmentUsage::class);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::EQUIPMENT_STATUSES, $this->status);
    }
}
