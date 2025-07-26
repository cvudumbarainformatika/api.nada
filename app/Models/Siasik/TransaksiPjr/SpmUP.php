<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmUP extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'transSpm';
    public $timestamps = false;

    // public function mapjurnal()
    // {
    //     return $this->belongsTo(SPM_GU::class, 'noSpm', 'noSpm');
    // }
}
