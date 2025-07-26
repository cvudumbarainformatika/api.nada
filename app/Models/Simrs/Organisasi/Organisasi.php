<?php

namespace App\Models\Simrs\Organisasi;

use App\Models\Satset\Satset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organisasi extends Model
{
    use HasFactory;
    protected $table = 'organisasi';
    protected $guarded = ['id'];


    public function satset()
    {
        return $this->hasOne(Satset::class, 'uuid', 'satset_uuid');
    }
}
