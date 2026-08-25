<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\PatientSignInLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Sign-up, for the two audiences that are genuinely different.
 *
 * A patient or caregiver gets a Patient record and a magic link — no password,
 * because this audience churns hard on account creation and the link in the
 * reminder mail is already the credential everywhere else in the app.
 *
 * A pharmacy or hospital gets an Organisation and its first admin User, with a
 * password, because the console is a desk tool used by named staff whose
 * actions are audited.
 */
class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register', [
            'languages' => config('medbuddy.languages'),
        ]);
    }

    public function store(Request $request)
    {
        $role = $request->input('role');

        return $role === 'clinic'
            ? $this->registerClinic($request)
            : $this->registerPatient($request);
    }

    private function registerPatient(Request $request)
    {
        $data = $request->validate([
            'role'  => ['required', Rule::in(['patient', 'caregiver'])],
            'name'  => ['required', 'string', 'max:120'],
            // The address is the credential. Unique because two records sharing
            // one mailbox would make the sign-in link ambiguous.
            'email' => ['required', 'email', 'max:190', Rule::unique('patients', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'lang'  => ['required', Rule::in(array_keys(config('medbuddy.languages')))],
            'messaging_consent' => ['accepted'],
            'research_consent'  => ['nullable', 'boolean'],
        ], [
            'messaging_consent.accepted' => 'Without consent to message you there is nothing for this app to do. You can withdraw it at any time.',
        ]);

        $patient = DB::transaction(function () use ($data) {
            $patient = Patient::create([
                'organisation_id' => $this->selfServeOrganisation()->id,
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'lang'  => $data['lang'],
                'enrolled_at' => now(),
            ]);

            // Two separate decisions, recorded separately, both revocable from
            // the patient's own profile page.
            Consent::create([
                'patient_id' => $patient->id, 'scope' => 'messaging', 'channel' => 'mail',
                'granted' => true, 'granted_at' => now(),
            ]);
            Consent::create([
                'patient_id' => $patient->id, 'scope' => 'research', 'channel' => null,
                'granted' => (bool) ($data['research_consent'] ?? false),
                'granted_at' => now(),
            ]);

            return $patient;
        });

        Audit::record('patient.self_enrolled', $patient, ['role' => $data['role']]);

        Notification::route('mail', $patient->email)->notify(new PatientSignInLink($patient));

        return redirect()->route('me.login')->with('sent', true);
    }

    private function registerClinic(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:120'],
            'email'             => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'phone'             => ['nullable', 'string', 'max:40'],
            'organisation_name' => ['required', 'string', 'max:150'],
            'city'              => ['nullable', 'string', 'max:80'],
            'kind'              => ['required', Rule::in(['hospital', 'pharmacy'])],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $org = Organisation::create([
                'name' => $data['organisation_name'],
                'kind' => $data['kind'],
                'city' => $data['city'] ?? null,
            ]);

            // The person who creates the organisation is its first admin;
            // everyone after them is invited from the Team page.
            return User::create([
                'organisation_id' => $org->id,
                'name'     => $data['name'],
                'email'    => $data['email'],
                'role'     => 'admin',
                'password' => Hash::make($data['password']),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        Audit::record('organisation.created', $user->organisation);

        return redirect()->route('clinic.overview')
            ->with('toast', 'Welcome. Enrol your first patient from Patients → Enrol.');
    }

    /**
     * Patients who sign themselves up have no clinic yet. They land in one
     * organisation so the record is never orphaned, and a clinic that later
     * takes them on moves them across.
     */
    private function selfServeOrganisation(): Organisation
    {
        return Organisation::firstOrCreate(
            ['name' => 'MedBuddy Direct'],
            ['kind' => 'pharmacy', 'city' => 'Lagos', 'emergency_number' => '112'],
        );
    }
}
