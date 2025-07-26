<?php

namespace App\Models\Simrs\Logx;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logsep extends Model
{
    use HasFactory;
    protected $table = 'log_sep';
    protected $guarded = ['id'];
    public $timestamps = false;
}
