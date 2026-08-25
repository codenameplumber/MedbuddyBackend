<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'kind', 'city', 'emergency_number'];

    public function patients() { return $this->hasMany(Patient::class); }
    public function users()    { return $this->hasMany(User::class); }
}
