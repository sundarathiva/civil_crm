<?php

namespace App\Http\Controllers;

use App\Models\WorkerAssignment;
use App\Models\WorkerAttendance;

class MyWorkController extends Controller
{
    public function index()
    {
        $worker = auth()->user()->worker;
        $assignments = $worker
            ? $worker->assignments()->with(['project.type', 'location'])->where('status', 'active')->latest('assigned_on')->get()
            : collect();
        $attendance = $worker
            ? $worker->attendance()->with(['project', 'location'])->latest('attended_on')->take(14)->get()
            : collect();

        return view('my-work', [
            'worker' => $worker,
            'assignments' => $assignments,
            'attendance' => $attendance,
            'today' => $worker
                ? WorkerAttendance::where('worker_id', $worker->id)->whereDate('attended_on', today())->first()
                : null,
        ]);
    }
}
