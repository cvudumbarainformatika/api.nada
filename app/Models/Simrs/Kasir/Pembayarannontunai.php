<?php

namespace App\Models\Simrs\Kasir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayarannontunai extends Model
{
    use HasFactory;
    protected $table = 'rs298';
    protected $guarded = ['id'];
}
