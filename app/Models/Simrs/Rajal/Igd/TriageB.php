<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriageB extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs251';
    protected $guarded = ['id'];
    public $timestamps = false;
    // protected $primaryKey = 'rs1';
    // protected $keyType = 'string';
}
