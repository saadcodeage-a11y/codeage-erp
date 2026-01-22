<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $activities = ActivityLog::with('user', 'subject')
            ->latest()
            ->paginate(20);

        return view('activity_logs.index', compact('activities'));
    }
}
