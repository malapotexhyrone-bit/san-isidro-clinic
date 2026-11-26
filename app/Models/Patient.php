<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $guarded = [];
    protected $casts = ['birth_date' => 'date'];
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function visits() { return $this->hasMany(Visit::class)->latest('arrived_at'); }
    public function getFullNameAttribute(): string { return preg_replace('/\s+/', ' ', trim("{$this->first_name} {$this->middle_name} {$this->last_name}")); }
}
