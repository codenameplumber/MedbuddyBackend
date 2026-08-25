<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organisation_id', 'name', 'email', 'phone', 'age', 'condition',
        'lang', 'caregiver_name', 'caregiver_email', 'enrolled_at', 'last_response_at',
    ];

    protected $casts = [
        'enrolled_at'      => 'datetime',
        'last_response_at' => 'datetime',
    ];

    public function organisation()  { return $this->belongsTo(Organisation::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
    public function doseEvents()    { return $this->hasMany(DoseEvent::class); }
    public function reminders()     { return $this->hasMany(Reminder::class); }
    public function reports()       { return $this->hasMany(PatientReport::class); }
    public function consents()      { return $this->hasMany(Consent::class); }

    public function activePrescriptions()
    {
        return $this->hasMany(Prescription::class)->where('active', true);
    }

    /** First name only. Reminder greetings never use the full name. */
    public function firstName(): string
    {
        $parts = explode(' ', trim($this->name));
        return $parts[0] ?: $this->name;
    }

    /**
     * Consent is per scope AND per channel, and is revocable. Anything that
     * sends a message must ask this first.
     */
    public function hasConsent(string $scope, ?string $channel = null): bool
    {
        return $this->consents()
            ->where('scope', $scope)
            ->when($channel, fn ($q) => $q->where('channel', $channel))
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    /** One source of truth, so a screen and a prompt never disagree. */
    public function langName(): string
    {
        return config('medbuddy.languages')[$this->lang] ?? 'English';
    }
}
