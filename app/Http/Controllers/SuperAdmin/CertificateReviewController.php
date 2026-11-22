<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Support\CertificateEligibility;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Super Admin (EDP) report: issued certificates whose course still has
 * activities/assignments without a passing final grade for that student
 * (issued before the rule existed, or a grade changed later). Nothing is
 * changed automatically; the EDP decides, e.g. by revoking the certificate
 * in Certificate Management. Read-only; the route is inside role:super_admin.
 */
class CertificateReviewController extends Controller
{
    public function index(Request $request)
    {
        $rows = Certificate::query()
            ->where('status', 'issued')
            ->with(['student', 'course'])
            ->orderByDesc('issued_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Certificate $certificate) => $certificate->course && $certificate->student)
            ->map(fn (Certificate $certificate) => [
                'certificate' => $certificate,
                'blockers' => CertificateEligibility::activityBlockers($certificate->course, (int) $certificate->student_id),
            ])
            ->filter(fn (array $row) => $row['blockers'] !== [])
            ->values();

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('super-admin.certificate-review.index', ['rows' => $paginator]);
    }
}
