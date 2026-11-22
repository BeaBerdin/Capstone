<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Read-only Audit Log for the Super Admin. The route sits inside
 * `role:super_admin`; there is no route that edits or deletes an entry.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $action = array_key_exists((string) $request->query('action'), AuditLog::ACTIONS)
            ? (string) $request->query('action')
            : null;

        $actorId = (int) $request->query('actor') ?: null;
        $target = trim((string) $request->query('target'));
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        $logs = AuditLog::query()
            ->with(['actor', 'target'])
            ->when($action, fn (Builder $query) => $query->where('action', $action))
            ->when($actorId, fn (Builder $query) => $query->where('actor_id', $actorId))
            ->when($target !== '', fn (Builder $query) => $query->whereHas('target', fn (Builder $user) => $user
                ->where('name', 'like', '%'.$target.'%')
                ->orWhere('email', 'like', '%'.$target.'%')))
            // The dates are entered in Philippine time.
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from->copy()->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to->copy()->endOfDay()->setTimezone(config('app.timezone'))))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $actors = User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->select('actor_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('super-admin.audit-log.index', [
            'logs' => $logs,
            'actions' => AuditLog::ACTIONS,
            'actors' => $actors,
            'filters' => [
                'action' => $action,
                'actor' => $actorId,
                'target' => $target,
                'from' => $from?->format('Y-m-d'),
                'to' => $to?->format('Y-m-d'),
            ],
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, 'Asia/Manila');
        } catch (\Throwable) {
            return null;
        }
    }
}
