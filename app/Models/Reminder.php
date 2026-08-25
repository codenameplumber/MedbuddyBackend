<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'dose_date', 'dose_time', 'slot', 'channel',
        'to', 'body', 'sent_at', 'opened_at', 'responded_at', 'error',
    ];

    protected $casts = [
        'dose_date'    => 'date',
        'sent_at'      => 'datetime',
        'opened_at'    => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function reports() { return $this->hasMany(PatientReport::class); }

    public function scopeSent($q)      { return $q->whereNotNull('sent_at'); }
    public function scopeResponded($q) { return $q->whereNotNull('responded_at'); }
}
