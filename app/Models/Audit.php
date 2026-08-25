<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only. Every read of a patient record lands here, which is a
 * regulatory requirement rather than a feature.
 */
class Audit extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'meta', 'ip'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, $subject = null, array $meta = []): self
    {
        return static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),
            'meta'         => $meta ?: null,
            'ip'           => request()?->ip(),
        ]);
    }
}
