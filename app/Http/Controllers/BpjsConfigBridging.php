<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LZCompressor\LZString;

class BpjsConfigBridging extends Controller
{
    // private $base_url;

    // public function __construct()
    // {
    //     $this->base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
    // }

    // public static function vclaim()
    // {
    //     return $this->base_url . 'vclaim-rest-dev';
    // }
    // public static function antreanrs()
    // {
    //     return $this->base_url . 'antreanrs_dev';
    // }

    public static function serviceName($name)
    {
        // VClaim : https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev
        // Antrean RS : https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev
        // Apotek : https://apijkn-dev.bpjs-kesehatan.go.id/apotek-rest-dev
        // Pcare : https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev
        // $this->service_name = $name;
        // $service = 'vclaim-rest-dev'
        // if ($this->service_name === 'antrean') {
        //     $service = 'vclaim-rest-dev';
        // } elseif($name === 'apotek'){
        //     $service = 'apotek-rest-dev';
        // } else {
        //     $service = 'pcare-rest-dev';
        // }

        // $base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/'.$service;
        return $name;
    }
    public static function getSignature()
    {
        $user_key = "fbad382d69383c78969f889077053ebb";

        $data = "31014";
        $secretKey = "3sY5CB0658";
        // Computes the timestamp
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        // Computes the signature by hashing the salt with the secret key as the key
        $signature = hash_hmac('sha256', $data . "&" . $tStamp, $secretKey, true);

        // base64 encode�
        $encodedSignature = base64_encode($signature);

        $data = array(
            'xconsid' => $data,
            'xtimestamp' => $tStamp,
            'xsignature' => $encodedSignature,
            'user_key' => $user_key,
            'secret_key' => $secretKey
        );

        return $data;
    }

    public static function getHeader()
    {
        $data = self::getSignature();
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-cons-id' => $data['xconsid'],
            'X-timestamp' => $data['xtimestamp'],
            'X-signature' => $data['xsignature'],
            'user_key' => $data['user_key'],
        ];
    }

    public static function stringDecrypt($key, $string)
    {
        // $key = $sign['xconsid'] . $sign['secret_key'] . $time;
        $encrypt_method = 'AES-256-CBC';
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
        return $output;
    }

    public static function decompress($string)
    {
        return LZString::decompressFromEncodedURIComponent($string);
    }
}
