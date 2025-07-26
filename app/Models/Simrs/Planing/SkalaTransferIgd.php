<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkalaTransferIgd extends Model
{
    use HasFactory;

    protected $table = 'skala_transfer_igd';
    protected $guarded = ['id'];
}
