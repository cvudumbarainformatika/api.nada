<?php

namespace App\Models\Simrs\Anamnesis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryPerkawinan extends Model
{
    use HasFactory;
    protected $table = 'history_perkawinan';
    protected $guarded = ['id'];
}
