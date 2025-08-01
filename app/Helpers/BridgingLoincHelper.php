<?php

namespace App\Helpers;

use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class BridgingLoincHelper
{
    /**
     * wawan
     */
    public static function getLoincByKode($kode)
    {
        // $url = 'https://fhir.loinc.org/CodeSystem/$lookup?system=http://loinc.org&code='.$kode;
        $url = 'https://loinc.regenstrief.org/searchapi/loincs?query=' . $kode;
        $response = Http::withBasicAuth('user', 'password')->acceptJson()->get($url); // user dan password harusnya di env

        return json_decode($response, true);
    }
}
