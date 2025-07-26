<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpkPanjar_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npkpanjar_heder';
    public $timestamps = false;
    public function npkrinci()
    {
        return $this->hasMany(NpkPanjar_Rinci::class, 'nonpk', 'nonpk');
    }

}
