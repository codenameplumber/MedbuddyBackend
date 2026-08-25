<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use Closure;
use Illuminate\Http\Request;

/**
 * Patients do not have passwords.
 *
 * They sign in with a signed emailed link, exactly like a dose reminder — the
 * same reasoning applies: this audience churns hard on account creation, and a
 * password wall costs more usage than it protects. The session holds only a
 * patient id, and it is re-validated on every request.
 */
class EnsurePatient
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('patient_id');
        $patient = $id ? Patient::find($id) : null;

        if (! $patient) {
            $request->session()->forget('patient_id');
            return redirect()->route('me.login');
        }

        app()->setLocale($patient->lang);
        $request->attributes->set('patient', $patient);

        view()->share('me', $patient);

        return $next($request);
    }
}
