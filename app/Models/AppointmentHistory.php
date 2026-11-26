<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentHistory extends Model
{
    protected $guarded = [];
    protected $casts = ['previous_date' => 'date', 'new_date' => 'date'];
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
