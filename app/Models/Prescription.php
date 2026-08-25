<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'prescribed_by', 'name', 'generic', 'strength', 'form',
        'purpose', 'times', 'days', 'starts_on', 'ends_on', 'ai_summary', 'active',
    ];

    protected $casts = [
        'times'      => 'array',
        'days'       => 'array',
        'ai_summary' => 'array',
        'starts_on'  => 'date',
        'ends_on'    => 'date',
        'active'     => 'boolean',
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function doseEvents() { return $this->hasMany(DoseEvent::class); }
    public function prescriber() { return $this->belongsTo(User::class, 'prescribed_by'); }

    /** Is this prescription live on the given date? */
    public function activeOn(CarbonInterface $date): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->starts_on && $date->lt($this->starts_on)) {
            return false;
        }
        if ($this->ends_on && $date->gt($this->ends_on)) {
            return false;
        }

        $days = $this->days;

        return empty($days) || in_array($date->dayOfWeek, $days, true);
    }

    public function dosesPerDay(): int
    {
        return count($this->times ?? []);
    }
}
