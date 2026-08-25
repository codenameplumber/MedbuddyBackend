<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoseEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'prescription_id', 'dose_date', 'dose_time',
        'status', 'source', 'recorded_at',
    ];

    protected $casts = [
        'dose_date'   => 'date',
        'recorded_at' => 'datetime',
    ];

    public function patient()      { return $this->belongsTo(Patient::class); }
    public function prescription() { return $this->belongsTo(Prescription::class); }

    public function scopeTaken($q) { return $q->where('status', 'taken'); }
}
