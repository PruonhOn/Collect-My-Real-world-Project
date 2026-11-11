<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    // ─── Settings ───

    public function settings()
    {
        $settings = SystemSetting::orderBy('group')->orderBy('label')->get();
        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($data['settings'] as $item) {
            SystemSetting::setValue($item['key'], $item['value'] ?? '');
        }

        ActivityLog::log('settings_updated', 'System settings were updated');

        return response()->json(['message' => 'Settings updated successfully']);
    }

    // ─── System Info ───

    public function systemInfo()
    {
        $info = [
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
                'url' => config('app.url'),
                'version' => app()->version(),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'db_driver' => DB::connection()->getDriverName(),
                'db_name' => DB::connection()->getDatabaseName(),
            ],
            'system' => [
                'os' => PHP_OS,
                'memory_limit' => ini_get('memory_limit'),
                'max_upload_size' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'storage' => [
                'storage_link' => Storage::exists('public') ? 'Linked' : 'Not linked',
                'disk_free' => $this->formatBytes(disk_free_space(storage_path())),
                'disk_total' => $this->formatBytes(disk_total_space(storage_path())),
            ],
        ];

        return response()->json($info);
    }

    // ─── Cache Management ───

    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');

        $actions = match ($type) {
            'config' => [['config:cache', null]],
            'route' => [['route:cache', null]],
            'view' => [['view:clear', null]],
            'cache' => [['cache:clear', null]],
            'optimize' => [['optimize:clear', null]],
            default => [
                ['config:cache', null],
                ['route:cache', null],
                ['view:clear', null],
                ['cache:clear', null],
                ['optimize:clear', null],
            ],
        };

        $results = [];
        foreach ($actions as [$cmd, $params]) {
            try {
                Artisan::call($cmd, $params ?? []);
                $results[] = ['command' => $cmd, 'status' => 'ok', 'output' => Artisan::output()];
            } catch (\Throwable $e) {
                $results[] = ['command' => $cmd, 'status' => 'error', 'output' => $e->getMessage()];
            }
        }

        ActivityLog::log('cache_cleared', "Cache cleared: {$type}");

        return response()->json(['message' => 'Cache cleared successfully', 'results' => $results]);
    }

    // ─── Maintenance Mode ───

    public function maintenanceMode(Request $request)
    {
        $request->validate(['enabled' => 'required|boolean']);

        if ($request->enabled) {
            Artisan::call('down', [
                '--secret' => $request->input('secret', 'bypass-' . str()->random(8)),
                '--render' => 'errors::503',
            ]);
            ActivityLog::log('maintenance_enabled', 'Maintenance mode was enabled');
            $message = 'Maintenance mode enabled';
        } else {
            Artisan::call('up');
            ActivityLog::log('maintenance_disabled', 'Maintenance mode was disabled');
            $message = 'Maintenance mode disabled';
        }

        return response()->json(['message' => $message, 'output' => Artisan::output()]);
    }

    // ─── Activity Logs ───

    public function activityLogs()
    {
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($logs);
    }

    public function clearActivityLogs()
    {
        ActivityLog::truncate();
        ActivityLog::log('activity_logs_cleared', 'All activity logs were cleared');
        return response()->json(['message' => 'Activity logs cleared']);
    }

    // ─── Helpers ───

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
