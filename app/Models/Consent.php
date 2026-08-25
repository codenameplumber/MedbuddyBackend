<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'scope', 'channel', 'granted', 'granted_at', 'revoked_at',
    ];

    protected $casts = [
        'granted'    => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
}
