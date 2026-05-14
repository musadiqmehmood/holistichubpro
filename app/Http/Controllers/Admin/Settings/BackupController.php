<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    use ApiResponse;
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

        // SECURITY: Pass password via MYSQL_PWD env var — never visible in `ps aux`.
        // Uses putenv() for cross-platform compatibility (Windows + Linux).
        $oldPwd = getenv('MYSQL_PWD');
        putenv('MYSQL_PWD=' . $db['password']);

        $descriptors = [
            0 => ['pipe', 'r'],   // stdin
            1 => ['file', $path, 'w'], // stdout → backup file
            2 => ['pipe', 'w'],   // stderr
        ];

        $args = [
            escapeshellarg($mysqldump),
            '--user=' . escapeshellarg($db['username']),
            '--host=' . escapeshellarg($db['host']),
            '--port=' . escapeshellarg($db['port'] ?? 3306),
            '--single-transaction',
            '--skip-lock-tables',
            escapeshellarg($db['database']),
        ];

        $cmd = implode(' ', $args);
        Log::info('Running backup', ['host' => $db['host'], 'database' => $db['database'], 'mysqldump' => $mysqldump]);

        // null env = inherits current process env (including MYSQL_PWD set above)
        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            putenv('MYSQL_PWD' . ($oldPwd !== false ? '=' . $oldPwd : ''));
            Log::error('Backup failed: could not start mysqldump process');
            return $this->serverError('Backup failed. Could not start database dump process.');
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        // Restore MYSQL_PWD env var
        putenv('MYSQL_PWD' . ($oldPwd !== false ? '=' . $oldPwd : ''));

        if ($exitCode !== 0 || !file_exists($path) || filesize($path) === 0) {
            @unlink($path);
            Log::error('Backup failed', ['exitCode' => $exitCode, 'stderr' => $stderr]);
            return $this->error('Backup failed. Ensure mysqldump is available and DB credentials are correct.', 500);
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

        return $this->success([
            'filename' => $filename,
            'size' => filesize($path),
            'created_at' => now()->toISOString(),
        ], 'Backup created successfully');
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

        return $this->success($list);
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

        return $this->deleted('Backup deleted successfully');
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
