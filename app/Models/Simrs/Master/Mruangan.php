<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mruangan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $table      = 'ruangs';
    protected $guarded = ['id'];

    public function conruangan()
    {
        $conruangan = new Mruangan;

        $conruangan->setConnection('sigarang');

        $wew = $conruangan->find(1);

        return $wew;
    }
}
