<?php

namespace App\Livewire\Me;

use App\Models\Patient;

/**
 * Every patient screen resolves its patient the same way: from the session,
 * re-read on each request.
 *
 * Nothing is trusted from a Livewire property. A component property round
 * trips through the browser, so holding the patient id there would make the
 * whole app a matter of editing one number in a payload.
 */
trait HasPatient
{
    public function me(): Patient
    {
        $patient = Patient::find(session('patient_id'));

        abort_unless($patient, 403);

        app()->setLocale($patient->lang);

        return $patient;
    }
}
