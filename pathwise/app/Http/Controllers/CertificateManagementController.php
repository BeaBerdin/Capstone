<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificateManagementController extends Controller
{
    public function index()
    {
        $certificates = Certificate::with([
            'student',
            'course'
        ])
        ->latest()
        ->paginate(15);

        return view(
            'certificate-management.index',
            compact('certificates')
        );
    }
}