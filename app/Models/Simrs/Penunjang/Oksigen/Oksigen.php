<?php

namespace App\Models\Simrs\Penunjang\Oksigen;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oksigen extends Model
{
    use HasFactory;
    protected $table = 'rs205';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = (int) $this->rs4;
        $harga2 = (int) $this->rs5;
        $harga3 = (int) $this->rs6;
        $subtotal = ($harga1+$harga2)*$harga3;
        return ($subtotal);
    }
}
