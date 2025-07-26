<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RencanaTerapiDokter extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rencanaterapidokter';
    protected $guarded = ['id'];
}
