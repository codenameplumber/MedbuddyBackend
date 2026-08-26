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
        return view('me.login', ['demoPatients' => self::demoPatients()]);
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

    /**
     * The seeded cohort, for the demo sign-in panel on /me/login.
     *
     * Empty unless the flag is on AND the patient is on the fabricated
     * domain, which is what makes leaving the flag on survivable: there is
     * nothing here to sign in as except invented people.
     *
     * @return \Illuminate\Support\Collection<int, Patient>
     */
    public static function demoPatients()
    {
        if (! config('medbuddy.demo_signin')) {
            return collect();
        }

        $cohort = Patient::query()
            ->where('email', 'like', '%'.config('medbuddy.demo_domain'))
            ->whereHas('activePrescriptions')
            ->orderBy('name')
            ->get();

        // A shortlist, not the whole cohort: 120 rows is a wall, and the point
        // of the panel is to get someone into the app in one tap. Two per
        // language keeps every translation reachable, which is the thing worth
        // showing.
        return $cohort
            ->groupBy('lang')
            ->flatMap(fn ($group) => $group->take(2))
            ->sortBy('lang')
            ->values();
    }

    /**
     * One-click sign-in as a seeded patient.
     *
     * Deliberately a POST behind the same two guards as the list itself, so a
     * link cannot be pasted anywhere and a real record can never be the
     * target. Audited like every other way into a patient record.
     */
    public function demoSignIn(Request $request)
    {
        abort_unless(config('medbuddy.demo_signin'), 404);

        $patient = Patient::findOrFail($request->integer('patient'));

        abort_unless(
            str_ends_with((string) $patient->email, config('medbuddy.demo_domain')),
            403,
            'Demo sign-in only works for the fabricated cohort.',
        );

        $this->startSessionFor($request, $patient);
        Audit::record('patient.demo_signed_in', $patient);

        return redirect()->route('me.today');
    }

    /** Signed route. The signature IS the credential. */
    public function consume(Request $request, Patient $patient)
    {
        $this->startSessionFor($request, $patient);
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
        // invalidate(), not forget()+regenerate(): the second one rotates the
        // session id but keeps every key in it, so anything the app stashed
        // about this patient would still be sitting there for whoever signs
        // in next on the same device. Phones get shared.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('me.login');
    }

    /**
     * Begin a session as this patient, with nothing carried over.
     *
     * The bug this exists to prevent: session()->regenerate() rotates the
     * session ID but preserves the data, so signing in as a second patient on
     * the same browser inherited the first one's screen state — including the
     * Ask thread, which is a record of what someone asked about their own
     * medicines. invalidate() flushes it, and is the correct answer to session
     * fixation either way.
     */
    private function startSessionFor(Request $request, Patient $patient): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('patient_id', $patient->id);
    }
}
