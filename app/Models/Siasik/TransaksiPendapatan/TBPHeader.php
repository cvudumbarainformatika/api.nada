<?php

namespace App\Models\Siasik\TransaksiPendapatan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TBPHeader extends Model
{
    use HasFactory;
    // protected $connection = 'rs_coba';
    protected $guarded = ['id'];
    protected $table = 'tbp';
    public $timestamps = false;

}
