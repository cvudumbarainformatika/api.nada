<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mnakes extends Model
{
    use HasFactory;
    protected $table = 'rs21';
    protected $guarded = [''];
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';
}
