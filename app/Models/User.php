<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['organisation_id', 'name', 'email', 'role', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function organisation() { return $this->belongsTo(Organisation::class); }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function canPrescribe(): bool
    {
        return in_array($this->role, ['admin', 'physician'], true);
    }
}
