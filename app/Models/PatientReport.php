<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'prescription_id', 'reminder_id', 'symptoms', 'severity',
        'note', 'audio_path', 'lang', 'source', 'took_dose', 'triage_category',
        'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'symptoms'    => 'array',
        'took_dose'   => 'boolean',
        'reviewed_at' => 'datetime',
        'severity'    => 'integer',
    ];

    public function patient()      { return $this->belongsTo(Patient::class); }
    public function prescription() { return $this->belongsTo(Prescription::class); }
    public function reminder()     { return $this->belongsTo(Reminder::class); }
    public function reviewer()     { return $this->belongsTo(User::class, 'reviewed_by'); }

    /** "Feeling fine" is not a complaint. It must never become a safety signal. */
    public function isComplaint(): bool
    {
        $s = $this->symptoms ?? [];
        return ! empty($s) && ! in_array('fine', $s, true);
    }

    public function severityLabel(): string
    {
        $labels = ['None', 'Mild', 'Moderate', 'Severe'];
        return $labels[$this->severity] ?? 'Unknown';
    }

    /** Only a voice note in a language we can transcribe is machine-readable. */
    public function needsHumanReview(): bool
    {
        return $this->audio_path && $this->lang !== 'en' && ! $this->reviewed_at;
    }
}
