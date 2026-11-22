<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateManagementController extends Controller
{
    public function index(Request $request)
    {
        $certificates = Certificate::with([
            'student',
            'course'
        ])
        ->whereHas('course', fn ($course) => $course->manageableBy($request->user()))
        ->latest()
        ->paginate(15);

        return view(
            'certificate-management.index',
            compact('certificates')
        );
    }
}