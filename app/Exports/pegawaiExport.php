<?php

namespace App\Exports;

use App\Models\Sigarang\Pegawai;
use Maatwebsite\Excel\Concerns\FromCollection;

class pegawaiExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Pegawai::all();
    }
}
