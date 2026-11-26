<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    protected $guarded = [];
    public function visit() { return $this->belongsTo(Visit::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
