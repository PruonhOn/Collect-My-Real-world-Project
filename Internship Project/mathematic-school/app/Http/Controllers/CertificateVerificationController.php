<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateVerificationController extends Controller
{
    public function show(Request $request)
    {
        $certificate = null;
        $valid = null;

        if ($request->has('certificate_id') && $request->certificate_id) {
            $certificate = Certificate::with(['student', 'course'])
                ->where('certificate_id', $request->certificate_id)
                ->first();

            $valid = $certificate ? true : false;
        }

        return view('certificates.verify', compact('certificate', 'valid'));
    }
}
