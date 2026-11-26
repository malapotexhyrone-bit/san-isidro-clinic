<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $guarded = [];
    public function visit() { return $this->belongsTo(Visit::class); }
    public function physician() { return $this->belongsTo(Physician::class); }
}
