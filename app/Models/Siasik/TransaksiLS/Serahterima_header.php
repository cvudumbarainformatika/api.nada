<?php

namespace App\Models\Siasik\TransaksiLS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serahterima_header extends Model
{
    use HasFactory;

    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'serahterima_heder';
    public $timestamps = false;
    public function rinci()
    {
        return $this->hasMany(Serahterima_rinci::class, 'noserahterimapekerjaan', 'noserahterimapekerjaan');
    }
}
