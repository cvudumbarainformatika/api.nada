<?php

namespace App\Models\Simrs\Kasir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paymentbankjatim extends Model
{
    use HasFactory;
    protected $table = 'payment_virtual';
    protected $guarded = ['id'];
}
