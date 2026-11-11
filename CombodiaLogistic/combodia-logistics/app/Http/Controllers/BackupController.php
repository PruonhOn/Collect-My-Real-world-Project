<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function index()
    {
        $backupPath = database_path('backups');
        $backups = [];

        if (File::isDirectory($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $file) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'date' => $file->getMTime(),
                ];
            }
        }

        rsort($backups);

        return view('backups.index', compact('backups'));
    }

    public function create()
    {
        $backupPath = database_path('backups');

        if (!File::isDirectory($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupPath . '/' . $filename;

        try {
            $exitCode = Artisan::call('db:dump', ['--path' => $filePath]);

            if ($exitCode === 0) {
                return redirect()->route('backups.index')->with('success', 'Backup created successfully: ' . $filename);
            }

            return back()->with('error', 'Backup creation failed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Backup creation failed: ' . $e->getMessage());
        }
    }

    public function download($file)
    {
        $backupPath = database_path('backups') . '/' . $file;

        if (!File::exists($backupPath)) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->download($backupPath, $file, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|string',
        ]);

        $backupPath = database_path('backups') . '/' . $request->backup_file;

        if (!File::exists($backupPath)) {
            return back()->with('error', 'Backup file not found.');
        }

        try {
            $exitCode = Artisan::call('db:import', ['--path' => $backupPath]);

            if ($exitCode === 0) {
                return redirect()->route('backups.index')->with('success', 'Database restored successfully.');
            }

            return back()->with('error', 'Database restore failed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Database restore failed: ' . $e->getMessage());
        }
    }
}
