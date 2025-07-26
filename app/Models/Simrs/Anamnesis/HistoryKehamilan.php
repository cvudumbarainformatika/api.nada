<?php

namespace App\Models\Simrs\Anamnesis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryKehamilan extends Model
{
    use HasFactory;
    protected $table = 'historykehamilanigd';
    protected $guarded = ['id'];
}
