<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtipeKhasusDiagnosa extends Model
{
    use HasFactory;
    protected $table = 'rs136';
    protected $guarded = ['id1'];
    public $timestamps = false;
}
