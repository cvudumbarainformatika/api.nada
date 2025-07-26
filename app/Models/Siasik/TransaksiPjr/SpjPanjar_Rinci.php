<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpjPanjar_Rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'spjpanjar_rinci';
    public $timestamps = false;
    public function spjheader()
    {
        return $this->belongsTo(SpjPanjar_Header::class, 'nospjpanjar', 'nospjpanjar');
    }
}
