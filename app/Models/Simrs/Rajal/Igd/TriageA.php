<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriageA extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs250';
    protected $guarded = ['id'];
    public $timestamps = false;
    // protected $primaryKey = 'rs1';
    // protected $keyType = 'string';

    public function triageb(){
        return $this->hasOne(TriageB::class, 'rs1', 'rs1');
    }
}
