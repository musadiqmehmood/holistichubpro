<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    private const BACKUP_DIR = 'backups';

    private function getMysqldumpPath(): string
    {
        // 1. Check if custom path is set in .env
        $customPath = env('MYSQLDUMP_PATH');
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        // 2. Search common WAMP/XAMPP/MAMP paths
        $possiblePaths = [
            'D:\\wamp\\bin\\mysql\\mysql*\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysqldump.exe',
            'C:\\wamp\\bin\\mysql\\mysql*\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server *\\bin\\mysqldump.exe',
            'C:\\Program Files (x86)\\MySQL\\MySQL Server *\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ];

        foreach ($possiblePaths as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                // Return the first found (sorted alphabetically, so latest version might be first)
                return $matches[0];
            }
        }

        // 3. Try to find via `where` command (Windows) or `which` (Unix)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $where = shell_exec('where mysqldump 2>nul');
            if ($where) {
                $paths = explode("\n", trim($where));
                if (!empty($paths[0]) && file_exists($paths[0])) {
                    return $paths[0];
                }
            }
        } else {
            $which = trim(shell_exec('which mysqldump 2>/dev/null'));
            if ($which && file_exists($which)) {
                return $which;
            }
        }

        // 4. Fallback – assume it's in PATH
        return 'mysqldump';
    }

    public function create(): JsonResponse
    {
        $dir = storage_path('app/' . self::BACKUP_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup_' . now()->format('Ymd_His') . '_' . Auth::id() . '.sql';
        $path = $dir . '/' . $filename;

        $db = config('database.connections.' . config('database.default'));

        $mysqldump = $this->getMysqldumpPath();

        // Build command – wrap executable in quotes in case of spaces in path
        $cmd = sprintf(
            '"%s" --user=%s --password=%s --host=%s --port=%s %s > "%s" 2>&1',
            $mysqldump,
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['host']),
            escapeshellarg($db['port'] ?? 3306),
            escapeshellarg($db['database']),
            $path
        );

        Log::info('Running backup command', ['cmd' => $cmd]);

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($path) || filesize($path) === 0) {
            @unlink($path);
            $detail = implode("\n", $output);
            Log::error('Backup failed', ['exitCode' => $exitCode, 'output' => $detail]);
            return response()->json([
                'message' => 'Backup failed. Ensure mysqldump is available and DB credentials are correct.',
                'detail' => $detail,
            ], 500);
        }

        // Purge backups older than 7 days
        foreach (glob($dir . '/*.sql') as $old) {
            if (filemtime($old) < strtotime('-7 days')) {
                @unlink($old);
            }
        }

        AuditLogService::log('backup_created', 'Database', 0, null, [
            'filename' => $filename,
            'size' => filesize($path),
        ]);

        return response()->json([
            'filename' => $filename,
            'size' => filesize($path),
            'created_at' => now()->toISOString(),
            'message' => 'Backup created successfully',
        ]);
    }

    public function index(): JsonResponse
    {
        $dir = storage_path('app/' . self::BACKUP_DIR);
        $files = glob($dir . '/*.sql') ?: [];

        $list = array_map(fn($f) => [
            'filename' => basename($f),
            'size' => filesize($f),
            'size_readable' => $this->humanFilesize(filesize($f)),
            'created_at' => date('Y-m-d H:i:s', filemtime($f)),
        ], $files);

        usort($list, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return response()->json($list);
    }

    public function download(string $file): BinaryFileResponse
    {
        $safeFilename = basename($file);
        $path = storage_path('app/' . self::BACKUP_DIR . '/' . $safeFilename);
        abort_unless(file_exists($path), 404, 'Backup file not found');

        AuditLogService::log('backup_downloaded', 'Database', 0, null, [
            'filename' => $safeFilename,
            'size' => filesize($path),
        ]);

        return response()->download($path);
    }

    public function destroy(string $file): JsonResponse
    {
        $path = storage_path('app/' . self::BACKUP_DIR . '/' . basename($file));
        abort_unless(file_exists($path), 404, 'Backup file not found');

        @unlink($path);

        AuditLogService::log('backup_deleted', 'Database', 0, ['filename' => basename($file)], null);

        return response()->json(['message' => 'Backup deleted successfully']);
    }

    private function humanFilesize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1) . ' ' . $unit;
            }
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' TB';
    }
}
