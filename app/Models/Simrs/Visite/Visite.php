<?php

namespace App\Models\Simrs\Visite;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visite extends Model
{
    use HasFactory;
    protected $table = 'rs140';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];
    public $timestamps = false;

    public function getSubtotalAttribute()
    {
        $harga1 = $this->rs4;
        $harga2 = $this->rs5;
        $subtotal = $harga1+$harga2;
        return ($subtotal);
    }
}
