<?php

namespace App\Models\Simrs\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedureM extends Model
{
    use HasFactory;
    protected $table = 'prosedur_klaimx';
    protected $guarded = ['id'];
    public $timestamps = false;
}
