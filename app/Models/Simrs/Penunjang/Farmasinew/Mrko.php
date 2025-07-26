<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mrko extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'master_rko';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
