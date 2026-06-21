<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.monitoring.settings.index', [
            'settings' => [
                'slow_query_threshold_ms' => MonitoringSetting::get('slow_query_threshold_ms', 500),
                'retention_days' => MonitoringSetting::get('retention_days', 90),
                'alert_cpu_threshold' => MonitoringSetting::get('alert_cpu_threshold', 90),
                'alert_memory_threshold' => MonitoringSetting::get('alert_memory_threshold', 90),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slow_query_threshold_ms' => 'required|integer|min:100|max:60000',
            'retention_days' => 'required|integer|min:7|max:365',
            'alert_cpu_threshold' => 'required|integer|min:50|max:100',
            'alert_memory_threshold' => 'required|integer|min:50|max:100',
        ]);

        foreach ($validated as $key => $value) {
            MonitoringSetting::set($key, $value);
        }

        return back()->with('success', 'Monitoring settings saved.');
    }
}
