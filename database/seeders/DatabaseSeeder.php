<?php

namespace Database\Seeders;

use App\Models\Bridge;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\EquipmentUsage;
use App\Models\Material;
use App\Models\Pillar;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\User;
use App\Models\Wall;
use App\Models\Worker;
use App\Models\WorkerAssignment;
use App\Models\WorkerAttendance;
use App\Services\ProgressService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = collect([
                ['super_admin', 'Super Admin', 'Full system access'],
                ['engineer', 'Engineer', 'Assigned projects, work, and report review'],
                ['site_engineer', 'Site Engineer', 'Site activity and daily reports'],
                ['worker', 'Worker', 'Assigned work and attendance'],
            ])->mapWithKeys(fn ($row) => [
                $row[0] => Role::create(['slug' => $row[0], 'name' => $row[1], 'description' => $row[2]]),
            ]);

            $admin = $this->user($roles['super_admin'], 'Meera Krishnan', 'admin@civora.test', '9840011100');
            $engineer = $this->user($roles['engineer'], 'Arjun Rao', 'engineer@civora.test', '9840012200');
            $sundar = $this->user($roles['site_engineer'], 'Sundar Rajan', 'site@civora.test', '9840013300');
            $lakshmi = $this->user($roles['site_engineer'], 'Lakshmi Devi', 'lakshmi@civora.test', '9840013311');
            $karthikUser = $this->user($roles['worker'], 'Karthik M.', 'worker@civora.test', '9840014400');

            $types = collect([
                'pillar-construction' => 'Pillar Construction',
                'wall-construction' => 'Wall Construction',
                'bridge-construction' => 'Bridge Construction',
                'other-civil-works' => 'Other Civil Works',
            ])->mapWithKeys(fn ($name, $slug) => [$slug => ProjectType::create(['name' => $name, 'slug' => $slug])]);

            $railway = $this->project($types['other-civil-works'], 'RTC-2026', 'Railway Track Construction', 'Southern Railway', 'Avadi', '2026-01-15', '2026-12-20', $engineer, $sundar, 'in_progress', 'Doubling and civil structures between Avadi and Ambattur.', $admin);
            $wallJob = $this->project($types['wall-construction'], 'NH48-WALL', 'NH-48 Retaining Wall', 'NHAI', 'Sriperumbudur', '2026-03-01', '2026-11-30', $engineer, $lakshmi, 'in_progress', 'Retaining wall along the widened carriageway.', $admin);
            $bridgeJob = $this->project($types['bridge-construction'], 'ADY-BR', 'Adyar Footbridge', 'Greater Chennai Corporation', 'Adyar', '2026-06-01', '2027-02-28', $engineer, $sundar, 'started', 'Pedestrian bridge across the Adyar creek approach.', $admin);
            $porur = $this->project($types['pillar-construction'], 'POR-PLR', 'Porur Pillar Package', 'CMRL', 'Porur', '2025-08-01', '2026-06-30', $engineer, $lakshmi, 'completed', 'Pier package handed over after final inspection.', $admin);
            $this->project($types['other-civil-works'], 'ENR-YARD', 'Ennore Yard Extension', 'Chennai Port', 'Ennore', '2026-11-01', '2027-08-31', $engineer, null, 'new', 'Yard extension awaiting mobilisation.', $admin);

            $avadi = ProjectLocation::create([
                'project_id' => $railway->id,
                'name' => 'Avadi Railway Section',
                'address' => 'Avadi, Chennai',
                'chainage' => '20+000 – 22+500',
                'latitude' => 13.1143000,
                'longitude' => 80.1018000,
                'site_engineer_id' => $sundar->id,
                'description' => 'Main pier and formation section.',
                'status' => 'active',
            ]);
            $ambattur = ProjectLocation::create([
                'project_id' => $railway->id,
                'name' => 'Ambattur Yard',
                'address' => 'Ambattur, Chennai',
                'chainage' => '22+500 – 24+000',
                'latitude' => 13.1147000,
                'longitude' => 80.1548000,
                'site_engineer_id' => $lakshmi->id,
                'description' => 'Yard approach and bridge span.',
                'status' => 'active',
            ]);
            $nhSite = ProjectLocation::create([
                'project_id' => $wallJob->id,
                'name' => 'Sriperumbudur Stretch',
                'address' => 'NH-48, Sriperumbudur',
                'chainage' => '38+200 – 41+800',
                'latitude' => 12.9675000,
                'longitude' => 79.9417000,
                'site_engineer_id' => $lakshmi->id,
                'status' => 'active',
            ]);
            $adyar = ProjectLocation::create([
                'project_id' => $bridgeJob->id,
                'name' => 'Adyar Creek Approach',
                'address' => 'Lattice Bridge Road, Adyar',
                'chainage' => '0+000 – 0+080',
                'latitude' => 13.0067000,
                'longitude' => 80.2570000,
                'site_engineer_id' => $sundar->id,
                'status' => 'active',
            ]);
            $porurSite = ProjectLocation::create([
                'project_id' => $porur->id,
                'name' => 'Porur Pier Line',
                'address' => 'Mount-Poonamallee Road, Porur',
                'chainage' => '12+100 – 12+600',
                'site_engineer_id' => $lakshmi->id,
                'status' => 'active',
            ]);

            $p1 = $this->pillar($railway, $avadi, 'P001', 100, 'completed', '2026-02-01', '2026-07-15', 'Foundation and shaft complete.');
            $this->pillar($railway, $avadi, 'P002', 70, 'in_progress', '2026-04-01', '2026-10-30', 'Pier cap in progress.');
            $this->pillar($railway, $avadi, 'P003', 40, 'in_progress', '2026-06-01', '2026-11-30', 'Reinforcement cage being placed.');
            $this->wall($railway, $avadi, 'W001', 180, 3.2, 50, 'in_progress', '2026-05-01', '2026-12-01', 'Return wall beside the formation.');
            $this->bridge($railway, $ambattur, 'B001', 42, 8.5, 30, 'in_progress', '2026-05-15', '2026-12-15', 'Abutment A1 cast. Deck yet to start.');

            $this->wall($wallJob, $nhSite, 'W-01', 4200, 4.5, 40, 'in_progress', '2026-03-10', '2026-11-15', 'Main retaining wall.');
            $this->wall($wallJob, $nhSite, 'W-02', 620, 2.4, 60, 'in_progress', '2026-04-01', '2026-10-01', 'Toe wall at the drain.');

            $this->bridge($bridgeJob, $adyar, 'FB-01', 36, 3.0, 20, 'in_progress', '2026-06-15', '2027-01-31', 'Piles driven. Pile cap next.');

            $this->pillar($porur, $porurSite, 'PP-01', 100, 'completed', '2025-09-01', '2026-03-01', 'Handed over.');
            $this->pillar($porur, $porurSite, 'PP-02', 100, 'completed', '2025-09-15', '2026-03-20', 'Handed over.');
            $this->pillar($porur, $porurSite, 'PP-03', 100, 'completed', '2025-10-01', '2026-04-15', 'Handed over.');

            $stock = app(StockService::class);
            $cement = $this->material('Cement', 'CEM-OPC', 'Cement', 'Bags', 100);
            $steel = $this->material('Steel', 'STL-FE500', 'Steel', 'Kg', 1000);
            $sand = $this->material('Sand', 'SND-RIV', 'Sand', 'Loads', 8);
            $aggregate = $this->material('Aggregate', 'AGG-20', 'Aggregate', 'Tonnes', 20);
            $bricks = $this->material('Bricks', 'BRK-CLS', 'Bricks', 'Nos', 800);
            $concrete = $this->material('Concrete', 'CON-M25', 'Concrete', 'm3', 10);

            $stock->apply($cement, 'opening', 500, '2026-08-01', null, null, 'Opening balance', $admin);
            $stock->apply($cement, 'received', 200, '2026-09-10', $railway->id, $avadi->id, 'Supplier receipt', $admin);
            $stock->apply($cement, 'issued', 100, '2026-09-18', $railway->id, $avadi->id, 'Issued to Avadi section', $admin);
            $stock->apply($cement, 'returned', 10, '2026-09-22', $railway->id, $avadi->id, 'Unused bags returned', $admin);

            $stock->apply($steel, 'opening', 8000, '2026-08-01', null, null, 'Opening balance', $admin);
            $stock->apply($steel, 'received', 2500, '2026-09-12', $railway->id, null, 'Yard receipt', $admin);
            $stock->apply($sand, 'opening', 20, '2026-08-01', null, null, 'Opening balance', $admin);
            $stock->apply($sand, 'used', 12, '2026-09-20', $railway->id, $avadi->id, 'Blinding concrete', $sundar);
            $stock->apply($aggregate, 'opening', 12, '2026-08-01', null, null, 'Opening balance', $admin);
            $stock->apply($bricks, 'opening', 400, '2026-08-01', null, null, 'Opening balance', $admin);
            $stock->apply($concrete, 'opening', 40, '2026-09-01', null, null, 'Opening balance', $admin);
            $stock->apply($concrete, 'received', 15, '2026-09-25', $wallJob->id, $nhSite->id, 'RMC delivery', $lakshmi);

            $jcb = Equipment::create([
                'name' => 'JCB 3DX', 'code' => 'JCB-001', 'equipment_type' => 'JCB',
                'registration_number' => 'TN-12-AB-4410', 'owner' => 'Civora Plant', 'operator_name' => 'Muthu',
                'project_id' => $railway->id, 'project_location_id' => $avadi->id, 'status' => 'working',
            ]);
            Equipment::create([
                'name' => 'Crawler Excavator', 'code' => 'EXC-014', 'equipment_type' => 'Excavator',
                'registration_number' => 'TN-09-CQ-2201', 'owner' => 'Hired — Vel Plant', 'operator_name' => 'Ravi',
                'project_id' => $wallJob->id, 'project_location_id' => $nhSite->id, 'status' => 'maintenance',
            ]);
            Equipment::create([
                'name' => 'Mobile Crane', 'code' => 'CRN-006', 'equipment_type' => 'Crane',
                'registration_number' => 'TN-01-CR-7782', 'owner' => 'Civora Plant', 'operator_name' => 'Senthil',
                'status' => 'available',
            ]);
            $mixer = Equipment::create([
                'name' => 'Concrete Mixer', 'code' => 'MIX-003', 'equipment_type' => 'Concrete Mixer',
                'owner' => 'Civora Plant', 'operator_name' => 'Anbu',
                'project_id' => $railway->id, 'project_location_id' => $avadi->id, 'status' => 'assigned',
            ]);
            Equipment::create([
                'name' => 'Tipper Truck', 'code' => 'TRK-021', 'equipment_type' => 'Truck',
                'registration_number' => 'TN-18-TT-1904', 'owner' => 'Hired', 'project_id' => $wallJob->id, 'status' => 'working',
            ]);
            Equipment::create([
                'name' => 'Site Generator', 'code' => 'GEN-002', 'equipment_type' => 'Generator',
                'owner' => 'Civora Plant', 'project_id' => $bridgeJob->id, 'project_location_id' => $adyar->id, 'status' => 'maintenance',
            ]);

            $karthik = $this->worker($karthikUser, 'WRK-001', 'Karthik M.', '9840014400', 'Mason', 'Pier masonry', $railway, $avadi, 950);
            $helper = $this->worker(null, 'WRK-002', 'Selvam P.', '9841010101', 'Helper', 'General help', $railway, $avadi, 650);
            $steelHand = $this->worker(null, 'WRK-003', 'Imran K.', '9841010102', 'Steel Worker', 'Bar bending', $railway, $avadi, 1100);
            $this->worker(null, 'WRK-004', 'Dinesh R.', '9841010103', 'Carpenter', 'Shuttering', $railway, $avadi, 1050);
            $this->worker(null, 'WRK-005', 'Murugan S.', '9841010104', 'Welder', 'Cage welding', $railway, $ambattur, 1000);
            $operator = $this->worker(null, 'WRK-006', 'Muthu K.', '9841010105', 'Operator', 'JCB', $railway, $avadi, 1200);
            $this->worker(null, 'WRK-007', 'Priya N.', '9841010106', 'General Worker', 'Site support', $wallJob, $nhSite, 700);
            $this->worker(null, 'WRK-008', 'Abdul Rahman', '9841010107', 'Mason', 'Block work', $wallJob, $nhSite, 900);
            $this->worker(null, 'WRK-009', 'Ganesh V.', '9841010108', 'Helper', 'Material shifting', $bridgeJob, $adyar, 650);
            $this->worker(null, 'WRK-010', 'Suresh B.', '9841010109', 'Operator', 'Mixer', $railway, $avadi, 1150);

            foreach ([$karthik, $helper, $steelHand, $operator] as $worker) {
                WorkerAssignment::create([
                    'worker_id' => $worker->id,
                    'project_id' => $railway->id,
                    'project_location_id' => $avadi->id,
                    'work_type' => 'pillar',
                    'work_id' => $p1->id,
                    'assigned_on' => '2026-09-01',
                    'status' => 'active',
                ]);
            }

            foreach ([$karthik, $helper, $steelHand, $operator] as $index => $worker) {
                WorkerAttendance::create([
                    'worker_id' => $worker->id,
                    'project_id' => $railway->id,
                    'project_location_id' => $avadi->id,
                    'attended_on' => now()->toDateString(),
                    'status' => $index === 1 ? 'half_day' : 'present',
                    'marked_by' => $sundar->id,
                ]);
            }

            $approved = DailyReport::create([
                'code' => 'DSR-2026-0001',
                'project_id' => $railway->id,
                'project_location_id' => $avadi->id,
                'report_date' => now()->subDay()->toDateString(),
                'site_engineer_id' => $sundar->id,
                'work_type' => 'pillar',
                'work_id' => $p1->id,
                'work_description' => 'Completed the last lift of pillar P001 and cleared shuttering.',
                'progress' => 100,
                'worker_count' => 12,
                'remarks' => 'Ready for engineer inspection.',
                'status' => 'approved',
                'review_note' => 'Accepted. Pier matches the drawing.',
                'reviewed_by' => $engineer->id,
                'reviewed_at' => now()->subDay(),
            ]);
            $approved->materials()->create(['material_id' => $sand->id, 'quantity' => 2, 'posted' => true]);
            $stock->apply($sand, 'used', 2, now()->subDay()->toDateString(), $railway->id, $avadi->id, 'Used on DSR-2026-0001', $sundar, $approved);
            $approved->equipmentLines()->create(['equipment_id' => $mixer->id, 'working_hours' => 5, 'fuel_used' => 18, 'remarks' => 'Mixer for coping.']);
            EquipmentUsage::create([
                'equipment_id' => $mixer->id, 'project_id' => $railway->id, 'project_location_id' => $avadi->id,
                'daily_report_id' => $approved->id, 'used_on' => now()->subDay()->toDateString(),
                'working_hours' => 5, 'fuel_used' => 18, 'work_description' => $approved->work_description, 'user_id' => $sundar->id,
            ]);
            $approved->workers()->create(['worker_id' => $karthik->id, 'attendance_status' => 'present']);

            $today = DailyReport::create([
                'code' => 'DSR-2026-0002',
                'project_id' => $railway->id,
                'project_location_id' => $avadi->id,
                'report_date' => now()->toDateString(),
                'site_engineer_id' => $sundar->id,
                'work_type' => 'pillar',
                'work_id' => Pillar::where('code', 'P002')->first()->id,
                'work_description' => 'Foundation work completed on pillar P002. Pier steel fixing continued.',
                'progress' => 70,
                'worker_count' => 15,
                'issues' => 'One vibrator is down. Borrowed a spare from Ambattur.',
                'remarks' => 'Foundation work completed.',
                'status' => 'submitted',
            ]);
            $today->materials()->create(['material_id' => $cement->id, 'quantity' => 50, 'posted' => false]);
            $today->materials()->create(['material_id' => $steel->id, 'quantity' => 500, 'posted' => false]);
            $today->equipmentLines()->create(['equipment_id' => $jcb->id, 'working_hours' => 6.5, 'fuel_used' => 42, 'remarks' => 'Excavation and dressing.']);
            EquipmentUsage::create([
                'equipment_id' => $jcb->id, 'project_id' => $railway->id, 'project_location_id' => $avadi->id,
                'daily_report_id' => $today->id, 'used_on' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '14:30',
                'working_hours' => 6.5, 'fuel_used' => 42, 'work_description' => $today->work_description, 'user_id' => $sundar->id,
            ]);
            foreach ([$karthik, $helper, $steelHand] as $worker) {
                $today->workers()->create([
                    'worker_id' => $worker->id,
                    'attendance_status' => $worker->is($helper) ? 'half_day' : 'present',
                ]);
            }

            $rejected = DailyReport::create([
                'code' => 'DSR-2026-0003',
                'project_id' => $bridgeJob->id,
                'project_location_id' => $adyar->id,
                'report_date' => now()->subDays(2)->toDateString(),
                'site_engineer_id' => $sundar->id,
                'work_type' => 'bridge',
                'work_id' => Bridge::where('code', 'FB-01')->first()->id,
                'work_description' => 'Pile cap shuttering started.',
                'progress' => 20,
                'worker_count' => 6,
                'remarks' => 'Need a clearer photograph of the pile group.',
                'status' => 'rejected',
                'review_note' => 'Photos are unclear. Retake the pile cap and resubmit.',
                'reviewed_by' => $engineer->id,
                'reviewed_at' => now()->subDay(),
            ]);
            $rejected->materials()->create(['material_id' => $concrete->id, 'quantity' => 4, 'posted' => false]);

            $progress = app(ProgressService::class);
            foreach (Project::all() as $project) {
                $progress->recalculate($project);
            }
        });
    }

    private function user(Role $role, string $name, string $email, string $phone): User
    {
        return User::create([
            'role_id' => $role->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    private function project(ProjectType $type, string $code, string $name, string $client, string $place, string $start, string $end, User $engineer, ?User $site, string $status, string $description, User $actor): Project
    {
        $project = Project::create([
            'code' => $code,
            'name' => $name,
            'project_type_id' => $type->id,
            'client_name' => $client,
            'location_summary' => $place,
            'start_date' => $start,
            'expected_completion_date' => $end,
            'engineer_id' => $engineer->id,
            'site_engineer_id' => $site?->id,
            'description' => $description,
            'status' => $status,
        ]);

        ProjectStatusHistory::create([
            'project_id' => $project->id,
            'from_status' => null,
            'to_status' => 'new',
            'user_id' => $actor->id,
            'note' => 'Project created.',
        ]);

        if ($status !== 'new') {
            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'from_status' => 'new',
                'to_status' => $status,
                'user_id' => $actor->id,
                'note' => 'Brought to '.$status.'.',
            ]);
        }

        return $project;
    }

    private function pillar(Project $project, ProjectLocation $location, string $code, float $progress, string $status, string $start, string $end, string $description): Pillar
    {
        return Pillar::create([
            'project_id' => $project->id,
            'project_location_id' => $location->id,
            'code' => $code,
            'progress' => $progress,
            'status' => $status,
            'start_date' => $start,
            'expected_completion_date' => $end,
            'description' => $description,
        ]);
    }

    private function wall(Project $project, ProjectLocation $location, string $code, float $length, float $height, float $progress, string $status, string $start, string $end, string $description): Wall
    {
        return Wall::create([
            'project_id' => $project->id,
            'project_location_id' => $location->id,
            'code' => $code,
            'length' => $length,
            'height' => $height,
            'progress' => $progress,
            'status' => $status,
            'start_date' => $start,
            'expected_completion_date' => $end,
            'description' => $description,
        ]);
    }

    private function bridge(Project $project, ProjectLocation $location, string $code, float $length, float $width, float $progress, string $status, string $start, string $end, string $description): Bridge
    {
        return Bridge::create([
            'project_id' => $project->id,
            'project_location_id' => $location->id,
            'code' => $code,
            'bridge_length' => $length,
            'bridge_width' => $width,
            'progress' => $progress,
            'status' => $status,
            'start_date' => $start,
            'expected_completion_date' => $end,
            'description' => $description,
        ]);
    }

    private function material(string $name, string $code, string $category, string $unit, float $minimum): Material
    {
        return Material::create([
            'name' => $name,
            'code' => $code,
            'category' => $category,
            'unit' => $unit,
            'minimum_stock' => $minimum,
            'current_stock' => 0,
            'status' => 'active',
        ]);
    }

    private function worker(?User $user, string $code, string $name, string $mobile, string $type, string $skill, Project $project, ProjectLocation $location, float $wage): Worker
    {
        return Worker::create([
            'user_id' => $user?->id,
            'code' => $code,
            'name' => $name,
            'mobile' => $mobile,
            'worker_type' => $type,
            'skill' => $skill,
            'project_id' => $project->id,
            'project_location_id' => $location->id,
            'daily_wage' => $wage,
            'status' => 'active',
        ]);
    }
}
