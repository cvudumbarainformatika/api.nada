<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mcounter extends Model
{
    use HasFactory;
    protected $table = 'rs1';
    public $timestamps = false;

    public function counterwew($data)
    {
        return $data->select([
            'rs13 as regrajal'
        ]);
    }
}
