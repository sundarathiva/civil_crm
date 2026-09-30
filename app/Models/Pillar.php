<?php

namespace App\Models;

use App\Models\Concerns\TracksWork;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'project_id', 'project_location_id', 'code', 'start_date',
    'expected_completion_date', 'status', 'progress', 'description',
])]
class Pillar extends Model
{
    use TracksWork;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_completion_date' => 'date',
            'progress' => 'decimal:1',
        ];
    }
}
