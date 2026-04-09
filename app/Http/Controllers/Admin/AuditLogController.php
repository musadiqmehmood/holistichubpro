<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = AuditLog::with('performer')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }

            if ($request->filled('performed_by')) {
                $query->where('performed_by', $request->performed_by);
            }

            if ($request->filled('from_date')) {
                $fromDate = Carbon::parse($request->from_date)->startOfDay();
                $query->where('created_at', '>=', $fromDate);
            }

            if ($request->filled('to_date')) {
                $toDate = Carbon::parse($request->to_date)->endOfDay();
                $query->where('created_at', '<=', $toDate);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhereHas('performer', function($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $perPage = $request->input('per_page', 10);
            $logs = $query->paginate($perPage);

            return response()->json($logs);

        } catch (\Exception $e) {
            Log::error('AuditLogController::index failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CRITICAL FIX: Stats method for Dashboard
     */
    public function stats()
    {
        try {
            $today = Carbon::today();
            $weekAgo = Carbon::now()->subDays(7);

            $stats = [
                'total_logs' => AuditLog::count(),
                'today_logs' => AuditLog::whereDate('created_at', $today)->count(),
                'week_logs' => AuditLog::where('created_at', '>=', $weekAgo)->count(),
                'top_actions' => AuditLog::select('action', DB::raw('count(*) as count'))
                    ->groupBy('action')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->get(),
                'entity_distribution' => AuditLog::select('entity_type', DB::raw('count(*) as count'))
                    ->groupBy('entity_type')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->get(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('AuditLogController::stats failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch audit stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function filters()
    {
        try {
            $actions = AuditLog::distinct()
                ->whereNotNull('action')
                ->pluck('action') ?? collect();

            $entityTypes = AuditLog::distinct()
                ->whereNotNull('entity_type')
                ->pluck('entity_type')
                ->map(function($type) {
                    return [
                        'value' => $type,
                        'label' => class_basename($type)
                    ];
                }) ?? collect();

            $performerIds = AuditLog::distinct()
                ->whereNotNull('performed_by')
                ->pluck('performed_by') ?? collect();

            $performers = User::whereIn('id', $performerIds)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'actions' => $actions,
                'entity_types' => $entityTypes,
                'performers' => $performers
            ]);

        } catch (\Exception $e) {
            Log::error('AuditLogController::filters failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch filter options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(AuditLog $auditLog)
    {
        try {
            Gate::authorize('delete', $auditLog);

            $auditLog->delete();

            return response()->json([
                'message' => 'Audit log deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('AuditLogController::destroy failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete audit log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        try {
            Gate::authorize('delete', AuditLog::class);

            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:audit_logs,id'
            ]);

            $count = AuditLog::whereIn('id', $request->ids)->delete();

            return response()->json([
                'message' => "{$count} audit logs deleted successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('AuditLogController::bulkDestroy failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clearAll()
    {
        try {
            Gate::authorize('delete', AuditLog::class);

            $count = AuditLog::count();
            AuditLog::truncate();

            return response()->json([
                'message' => "All {$count} audit logs cleared successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('AuditLogController::clearAll failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to clear audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Export audit logs to CSV (FIX B-13)
     */
    public function export(Request $request)
    {
        try {
            $this->authorize('viewAny', AuditLog::class);

            $query = AuditLog::with('performer')->orderBy('created_at', 'desc');

            // Apply filters (same as index)
            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }
            if ($request->filled('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }
            if ($request->filled('performed_by')) {
                $query->where('performed_by', $request->performed_by);
            }
            if ($request->filled('from_date')) {
                $query->where('created_at', '>=', Carbon::parse($request->from_date)->startOfDay());
            }
            if ($request->filled('to_date')) {
                $query->where('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhereHas('performer', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            }

            $logs = $query->get();

            $csv = Writer::createFromString('');
            $csv->insertOne(['ID', 'Action', 'Entity Type', 'Entity ID', 'Performed By (User)', 'IP Address', 'User Agent', 'Old Values', 'New Values', 'Created At']);

            foreach ($logs as $log) {
                $csv->insertOne([
                    $log->id,
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->performer?->name ?? 'System (ID: '.($log->performed_by ?? 'null').')',
                    $log->ip_address,
                    $log->user_agent,
                    json_encode($log->old_values),
                    json_encode($log->new_values),
                    $log->created_at->toDateTimeString(),
                ]);
            }

            return response($csv->toString(), 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="audit-logs-'.date('Y-m-d').'.csv"',
            ]);

        } catch (\Exception $e) {
            Log::error('AuditLogController::export failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to export audit logs', 'error' => $e->getMessage()], 500);
        }
    }

}
