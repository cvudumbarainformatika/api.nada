<?php

namespace App\Models\Simrs\Sharing;

use App\Models\Simrs\Master\Mpasien;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharingTrans extends Model
{
    use HasFactory;
    protected $table = 'sharingRajal';
    protected $guarded = ['id'];

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }
}
