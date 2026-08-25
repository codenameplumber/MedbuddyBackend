<?php

namespace App\Http\Controllers;

use App\Models\DoseEvent;
use App\Models\PatientReport;
use App\Models\Reminder;
use App\Services\TriageService;
use Illuminate\Http\Request;

/**
 * The page a reminder email opens.
 *
 * Three rules, all deliberate:
 *
 *  1. NO LOGIN. The route is protected by Laravel's signed-URL middleware,
 *     scoped to one reminder and expiring with its dose window. A password
 *     wall here would cost more responses than it protects.
 *
 *  2. ONE PAGE, ONE SUBMIT. The browser prototype used three steps; on a bad
 *     connection each step is another round trip that can fail. Everything is
 *     disclosed progressively on the client and posted once.
 *
 *  3. TAP BEFORE TYPE. The symptom grid yields clean structured data with zero
 *     literacy and zero speech-to-text, so the cohort view works regardless of
 *     which languages ASR happens to support.
 */
class RespondController extends Controller
{
    public function show(Reminder $reminder)
    {
        $patient = $reminder->patient;
        app()->setLocale($patient->lang);

        $reminder->forceFill(['opened_at' => $reminder->opened_at ?? now()])->save();

        return view('respond.show', [
            'reminder' => $reminder,
            'patient'  => $patient,
            'symptoms' => config('medbuddy.symptoms'),
        ]);
    }

    public function store(Request $request, Reminder $reminder, TriageService $triage)
    {
        $patient = $reminder->patient;
        app()->setLocale($patient->lang);

        $data = $request->validate([
            'took'     => ['required', 'in:yes,no'],
            'symptoms' => ['array'],
            'symptoms.*' => ['string', 'in:'.implode(',', config('medbuddy.symptoms'))],
            'severity' => ['nullable', 'integer', 'between:0,3'],
            'note'     => ['nullable', 'string', 'max:2000'],
            'audio'     => ['nullable', 'file', 'max:10240', 'mimetypes:audio/webm,audio/ogg,audio/mp4,audio/mpeg,audio/wav,video/webm'],
        ]);

        $took     = $data['took'] === 'yes';
        $symptoms = array_values($data['symptoms'] ?? []);

        // "Feeling fine" is exclusive. Without this the cohort fills with
        // "fine + rash", which means nothing.
        if (in_array('fine', $symptoms, true)) {
            $symptoms = ['fine'];
        }

        $isComplaint = $symptoms && ! in_array('fine', $symptoms, true);
        $severity    = $isComplaint ? (int) ($data['severity'] ?? 1) : 0;

        // The same deterministic gate the patient app uses. A free-text note
        // describing a crisis must not quietly become a routine symptom report.
        $verdict = $triage->check($data['note'] ?? null, $patient->lang);

        // Which medicine was due in this window? Best attribution available
        // without asking a patient to name a drug.
        $prescription = $patient->activePrescriptions()->get()
            ->first(fn ($rx) => in_array($reminder->dose_time, $rx->times ?? [], true));

        if ($took && $prescription) {
            DoseEvent::updateOrCreate(
                [
                    'prescription_id' => $prescription->id,
                    'dose_date'       => $reminder->dose_date,
                    'dose_time'       => $reminder->dose_time,
                ],
                [
                    'patient_id'  => $patient->id,
                    'status'      => 'taken',
                    'source'      => 'link',     // stronger provenance than recalled later
                    'recorded_at' => now(),
                ],
            );
        }

        // Private disk, never storage:link: a patient describing a side
        // effect is health data and a public path is a guessable one.
        $audioPath = $request->hasFile('audio')
            ? $request->file('audio')->store('r'.$reminder->id, 'voice')
            : null;

        if ($symptoms || filled($data['note'] ?? null) || $audioPath || ! $took) {
            PatientReport::create([
                'patient_id'      => $patient->id,
                'prescription_id' => $prescription?->id,
                'reminder_id'     => $reminder->id,
                'symptoms'        => $symptoms ?: ['fine'],
                'severity'        => $severity,
                'note'            => $data['note'] ?? null,
                'audio_path'      => $audioPath,
                'lang'            => $patient->lang,
                'source'          => 'link',
                'took_dose'       => $took,
                'triage_category' => $verdict['crisis'] ? $verdict['category'] : null,
            ]);
        }

        $reminder->forceFill(['responded_at' => now()])->save();
        $patient->forceFill(['last_response_at' => now()])->save();

        if ($verdict['crisis']) {
            return view('respond.emergency', [
                'patient'  => $patient,
                'category' => $verdict['category'],
                'guidance' => $triage->guidance($verdict['category']),
                'number'   => $patient->organisation->emergency_number ?? '112',
            ]);
        }

        return view('respond.done', ['patient' => $patient, 'took' => $took]);
    }
}
