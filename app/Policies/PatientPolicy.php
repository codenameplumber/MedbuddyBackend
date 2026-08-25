<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Role scoping. Everyone signed in can read the cohort; writing is narrower.
 *
 *   admin       everything
 *   physician   prescribe, enrol, manage consent
 *   pharmacist  enrol, manage consent, dispense notes
 *   nurse       read, triage complaints, send reminders
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        // One organisation per deployment today, but scope it now so adding a
        // second does not silently expose one clinic's patients to another.
        return $user->organisation_id === $patient->organisation_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'physician', 'pharmacist'], true);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient) && $this->create($user);
    }

    public function manageConsent(User $user, Patient $patient): bool
    {
        return $this->update($user, $patient);
    }

    public function sendReminder(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);   // nurses need this
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->role === 'admin' && $this->view($user, $patient);
    }
}
