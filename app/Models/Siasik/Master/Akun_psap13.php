<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun_psap13 extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akun_psap13';
    public $timestamps = false;
    protected $appends = ['rekening'];
    public function getRekeningAttribute(){
        return "{$this->kode1}.{$this->kode2}.{$this->kode3}.{$this->kode4}";
    }
}
