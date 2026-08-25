<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Patient;
use App\Notifications\PatientSignInLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

class PatientAuthController extends Controller
{
    public function show()
    {
        return view('me.login');
    }

    public function send(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        // Rate limited per address: the link is the credential, so requesting
        // one must not be a way to spam an inbox.
        $key = 'patient-link:'.mb_strtolower($data['email']);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('sent', true);   // same response either way
        }
        RateLimiter::hit($key, 900);

        $patient = Patient::where('email', $data['email'])->first();

        if ($patient) {
            Notification::route('mail', $patient->email)->notify(new PatientSignInLink($patient));
            Audit::record('patient.signin_link_sent', $patient);
        }

        // Never reveal whether an address is enrolled.
        return back()->with('sent', true);
    }

    /** Signed route. The signature IS the credential. */
    public function consume(Request $request, Patient $patient)
    {
        $request->session()->regenerate();
        $request->session()->put('patient_id', $patient->id);
        Audit::record('patient.signed_in', $patient);

        return redirect()->route('me.today');
    }

    /**
     * SOS. Deliberately a plain server-rendered page with no Livewire and no
     * network dependency beyond the one request: the phone number and the
     * medicine list must render even when everything else is failing.
     */
    public function emergency(Request $request)
    {
        $patient = $request->attributes->get('patient')->load('activePrescriptions', 'organisation');

        return view('me.emergency', [
            'patient' => $patient,
            'number'  => $patient->organisation?->emergency_number ?? '112',
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('patient_id');
        $request->session()->regenerate();

        return redirect()->route('me.login');
    }
}
