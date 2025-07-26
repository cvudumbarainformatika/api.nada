<?php

namespace App\Models\Simrs\DischargePlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryPulang extends Model
{
    use HasFactory;
    protected $table = 'rs242_baru_summary';
    protected $guarded = ['id'];
}
