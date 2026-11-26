<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $guarded = [];
    protected $casts = ['arrived_at' => 'datetime', 'completed_at' => 'datetime'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function physician() { return $this->belongsTo(Physician::class); }
    public function vitalSign() { return $this->hasOne(VitalSign::class); }
    public function consultation() { return $this->hasOne(Consultation::class); }
}
