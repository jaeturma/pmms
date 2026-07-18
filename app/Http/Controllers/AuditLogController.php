<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    use SearchesAndPaginates;

    /**
     * Searchable, filterable audit trail for administrators. The route is
     * guarded by the `administer` gate (`can:administer` middleware).
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);
        $action = trim((string) $request->query('action', ''));

        $query = AuditLog::query()
            ->with('user:id,name')
            ->latest('id');

        if ($action !== '') {
            $query->where('action', $action);
        }

        $this->applySearch($query, $search, ['action', 'user.name']);

        return Inertia::render('audit/index', [
            'logs' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user?->name,
                    'subject' => $log->auditable_type === null
                        ? null
                        : class_basename($log->auditable_type).' #'.$log->auditable_id,
                    'context' => $log->context,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toDayDateTimeString(),
                ]),
            'filters' => [
                'search' => $search,
                'action' => $action === '' ? null : $action,
            ],
            'actionOptions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}
