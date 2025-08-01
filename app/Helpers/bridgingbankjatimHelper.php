<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class bridgingbankjatimHelper
{
    public static function createqris($request)
    {
        $url = "https://jatimva.bankjatim.co.id/MC/Qris/Dynamic";
        $total = $request->total;
        $bj = 0.4;
        $totalall = (int) $total + (int) ($total * $bj / 100);
        $tglsekarang = date('y-m-d');
        $billNumber_tampung = $request->nota;
        $terminalUser_tampung = 'U012001';
        $merchanthashkey = '8M9R0BZE21';


        $merchantPan = '';
        $hashcodeKey = hash("sha256", $merchantPan . '' . $billNumber_tampung . '' . $terminalUser_tampung . '' . $merchanthashkey);
        $billNumber = $billNumber_tampung;
        $purposetrx = $request->noreg;
        $storelabel = ''; // nama rs
        $customerlabel = 'PUBLIC';
        $terminalUser = $terminalUser_tampung;
        $expiredDate = date('Y-m-d 23:59:59');;
        $amount = $totalall;
        $data = [
            'merchantPan' => $merchantPan,
            'hashcodeKey' => $hashcodeKey,
            'billNumber' => $billNumber,
            'purposetrx' => $purposetrx,
            'storelabel' => $storelabel,
            'customerlabel' => $customerlabel,
            'terminalUser' => $terminalUser,
            'expiredDate' => $expiredDate,
            'amount' => $amount
        ];
        $myvars = json_encode($data);
        $reqjatim = self::reqqris('POST', $url, $myvars);
        return $reqjatim;
    }

    public static function reqqris($method, $url, $myvars)
    {
        // $url = 'https://jatimva.bankjatim.co.id/MC/Qris/Dynamic';

        $session = curl_init($url);
        $arrheader =  array(
            //'Accept: application/json',
            'Content-Type: application/json',
        );

        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HTTPHEADER, $arrheader);
        curl_setopt($session, CURLOPT_VERBOSE, true);

        if ($method == 'POST') {
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        if ($method == 'PUT') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        if ($method == 'GET') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }
        if ($method == 'DELETE') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($session);
        return json_decode($response);
    }
}
