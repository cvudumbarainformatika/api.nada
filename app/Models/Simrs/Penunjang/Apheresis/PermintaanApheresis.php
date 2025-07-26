<?php

namespace App\Models\Simrs\Penunjang\Apheresis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanApheresis extends Model
{
    use HasFactory;
    protected $table = 'tpermintaanapheresis';
    protected $guarded = ['id'];
}
