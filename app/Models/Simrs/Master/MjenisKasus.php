<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MjenisKasus extends Model
{
    use HasFactory;
    protected $table = 'm_jenis_kasus';
    protected $guarded = ['id'];
}
