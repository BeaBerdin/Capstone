<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

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

        // Matches the person who acted OR the account affected.
        $user = mb_substr(trim((string) $request->query('user')), 0, 100);
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        $matchesUser = fn (Builder $query) => $query
            ->where('name', 'like', '%'.$user.'%')
            ->orWhere('email', 'like', '%'.$user.'%');

        $logs = AuditLog::query()
            ->with(['actor', 'target'])
            ->when($action, fn (Builder $query) => $query->where('action', $action))
            ->when($user !== '', fn (Builder $query) => $query->where(fn (Builder $either) => $either
                ->whereHas('actor', $matchesUser)
                ->orWhereHas('target', $matchesUser)))
            // Dates are entered in Philippine time.
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from->copy()->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to->copy()->endOfDay()->setTimezone(config('app.timezone'))))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('super-admin.audit-log.index', [
            'logs' => $logs,
            'actions' => AuditLog::ACTIONS,
            'filters' => [
                'action' => $action,
                'user' => $user,
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
        } catch (Throwable) {
            return null;
        }
    }
}
