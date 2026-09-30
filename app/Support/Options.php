<?php

namespace App\Support;

class Options
{
    public const PROJECT_STATUSES = [
        'new' => 'New',
        'started' => 'Started',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
    ];

    public const WORK_STATUSES = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ];

    public const LOCATION_STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    public const EQUIPMENT_STATUSES = [
        'available' => 'Available',
        'assigned' => 'Assigned',
        'working' => 'Working',
        'maintenance' => 'Maintenance',
        'unavailable' => 'Unavailable',
    ];

    public const EQUIPMENT_TYPES = [
        'JCB' => 'JCB',
        'Excavator' => 'Excavator',
        'Crane' => 'Crane',
        'Concrete Mixer' => 'Concrete Mixer',
        'Truck' => 'Truck',
        'Tractor' => 'Tractor',
        'Generator' => 'Generator',
        'Other' => 'Other',
    ];

    public const WORKER_TYPES = [
        'Mason' => 'Mason',
        'Helper' => 'Helper',
        'Carpenter' => 'Carpenter',
        'Steel Worker' => 'Steel Worker',
        'Welder' => 'Welder',
        'Operator' => 'Operator',
        'General Worker' => 'General Worker',
    ];

    public const ATTENDANCE = [
        'present' => 'Present',
        'absent' => 'Absent',
        'half_day' => 'Half Day',
        'leave' => 'Leave',
        'overtime' => 'Overtime',
    ];

    public const REPORT_STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const STOCK_TYPES = [
        'opening' => 'Opening Stock',
        'received' => 'Material Received',
        'issued' => 'Material Issued',
        'used' => 'Material Used',
        'returned' => 'Material Returned',
    ];

    public const WORK_TYPES = [
        'pillar' => 'Pillar',
        'wall' => 'Wall',
        'bridge' => 'Bridge',
        'other' => 'Other Civil Works',
    ];

    public const USER_STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    public const MATERIAL_CATEGORIES = [
        'Cement' => 'Cement',
        'Steel' => 'Steel',
        'Sand' => 'Sand',
        'Aggregate' => 'Aggregate',
        'Bricks' => 'Bricks',
        'Concrete' => 'Concrete',
        'Other' => 'Other',
    ];

    public static function label(array $map, ?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }

        return $map[$key] ?? str($key)->replace('_', ' ')->title()->toString();
    }
}
