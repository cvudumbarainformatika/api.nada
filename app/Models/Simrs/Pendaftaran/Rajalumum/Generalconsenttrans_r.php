<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generalconsenttrans_r extends Model
{
    use HasFactory;
    protected $table = 'generalconsent_rinci';
    protected $guarded = ['id'];
}
