<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtindakanX extends Model
{
    use HasFactory;
    protected $table = 'rs30z';
    protected $guarded = ['id'];
    public $timestamps = false;
}
