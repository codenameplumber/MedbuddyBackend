<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function create(User $user): bool
    {
        // Prescribing is a clinical act. Pharmacists and nurses cannot.
        return $user->canPrescribe();
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->canPrescribe()
            && $user->organisation_id === $prescription->patient?->organisation_id;
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $this->update($user, $prescription);
    }
}
