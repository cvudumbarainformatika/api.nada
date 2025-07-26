<?php

namespace App\Models\Siasik\Akuntansi\Jurnal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalUmum_Rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'jurnalumum_rinci';
    public $timestamps = false;
}
