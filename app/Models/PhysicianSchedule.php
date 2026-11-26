<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicianSchedule extends Model
{
    protected $guarded = [];
    protected $casts = ['is_available' => 'boolean'];
    public function physician() { return $this->belongsTo(Physician::class); }
}
