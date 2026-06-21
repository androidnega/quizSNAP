<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\SystemAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = SystemAuditLog::query()->orderByDesc('occurred_at');

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return view('admin.monitoring.activity.index', [
            'logs' => $query->paginate(30)->withQueryString(),
        ]);
    }
}
