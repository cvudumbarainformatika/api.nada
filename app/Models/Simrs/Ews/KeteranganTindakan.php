<?php

namespace App\Models\Simrs\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeteranganTindakan extends Model
{
    use HasFactory;
    protected $table = 'keterangantindakan';
    protected $guarded = ['id'];
}
