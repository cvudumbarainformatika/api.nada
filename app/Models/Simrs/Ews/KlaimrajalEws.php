<?php

namespace App\Models\Simrs\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlaimrajalEws extends Model
{
    use HasFactory;
    protected $table = 'klaim_trans_rajalx';
    protected $guarded = ['id'];
    public $timestamps = false;
}
