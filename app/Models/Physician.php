<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Physician extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function user() { return $this->belongsTo(User::class); }
    public function schedules() { return $this->hasMany(PhysicianSchedule::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function visits() { return $this->hasMany(Visit::class); }
}
