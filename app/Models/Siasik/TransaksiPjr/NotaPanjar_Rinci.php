<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaPanjar_Rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'notapanjar_rinci';
    public $timestamps = false;
    public function spj_head()
    {
        return $this->hasMany(SpjPanjar_Header::class, 'notapanjar', 'nonotapanjar');
    }
}
