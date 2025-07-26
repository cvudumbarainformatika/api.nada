<?php

namespace App\Models\Simrs\Bpjs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsCheckin extends Model
{
    use HasFactory;
    protected $table = 'bpjs_checkin';
    protected $guarded = ['id'];
}
