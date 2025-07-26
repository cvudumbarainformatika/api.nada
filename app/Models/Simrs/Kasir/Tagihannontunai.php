<?php

namespace App\Models\Simrs\Kasir;

use App\Models\Simrs\Master\Mpasien;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihannontunai extends Model
{
    use HasFactory;
    protected $table = 'rs297';
    protected $guarded = ['id'];

    public function flagbayar()
    {
        return $this->hasMany(Pembayarannontunai::class, 'rs1','rs4');
    }

    public function mpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1','rs3');
    }
}
