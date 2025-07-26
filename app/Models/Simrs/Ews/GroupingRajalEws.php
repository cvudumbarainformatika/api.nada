<?php

namespace App\Models\Simrs\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupingRajalEws extends Model
{
    use HasFactory;
    protected $table = 'grouping_klaim_rajalx';
    protected $guarded = ['id'];
    public $timestamps = false;
}
