<?php

namespace App\Models\Siasik\TransaksiLS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpkLS_heder extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npkls_heder';
    public $timestamps = false;

    public function npklsrinci()
    {
        return $this->hasMany(NpkLS_rinci::class, 'nonpk', 'nonpk');
    }
    public function npdls()
    {
        return $this->hasMany(NpdLS_heder::class, 'nonpk', 'nonpk');
    }
}
