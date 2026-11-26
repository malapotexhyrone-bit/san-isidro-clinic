<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const STATUSES = ['Scheduled', 'Arrived', 'Completed', 'Cancelled', 'Rescheduled', 'No-show', 'Walk-in'];
    public const ACTIVE_STATUSES = ['Scheduled', 'Arrived', 'Rescheduled'];
    protected $guarded = [];
    protected $casts = [
        'appointment_date' => 'date',
        'verified_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function physician() { return $this->belongsTo(Physician::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function visit() { return $this->hasOne(Visit::class); }
    public function histories() { return $this->hasMany(AppointmentHistory::class)->latest(); }
}
