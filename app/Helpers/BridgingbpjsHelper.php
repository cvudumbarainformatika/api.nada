<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class BridgingbpjsHelper
{

    public static function ws_url(string $name, $param)
    {
        //$base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
        $base_url = 'https://apijkn.bpjs-kesehatan.go.id/';
        $service_name = 'vclaim-rest';
        if ($name === 'antrean') {
            $service_name = 'antreanrs';
        } else if ($name === 'apotek') {
            $service_name = 'apotek-rest';
        } else if ($name === 'pcare') {
            $service_name = 'apotek-rest';
        } else if ($name === 'vclaim') {
            $service_name = 'vclaim-rest';
        } else if ($name === 'icare') {
            $service_name = 'wsihs';
        } else {
            $service_name = 'vclaim-rest';
        }

        $url = $base_url . $service_name . '/' . $param;
        return $url;
    }
    public static function ws_url_dev(string $name, $param)
    {
        $base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
        // $base_url = 'https://apijkn.bpjs-kesehatan.go.id/';
        $service_name = 'vclaim-rest';
        if ($name === 'antrean') {
            $service_name = 'antreanrs_dev';
        } else if ($name === 'apotek') {
            $service_name = 'apotek-rest-dev';
        } else if ($name === 'pcare') {
            $service_name = 'pcare-rest-dev';
        } else if ($name === 'vclaim') {
            $service_name = 'vclaim-rest-dev';
        } else if ($name === 'icare') {
            $service_name = 'ihs_dev';
        } else {
            $service_name = 'vclaim-rest-dev';
        }

        $url = $base_url . $service_name . '/' . $param;
        return $url;
    }


    public static function get_url(string $name, $param)
    {

        $url = self::ws_url($name, $param);
        //  $url = self::ws_url_dev($name, $param);


        $sign = self::getSignature($name);
        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];

        $header = self::getHeader($sign);
        $response = Http::withHeaders($header)->get($url);
        // return $response;
        $data = json_decode($response, true);
        // return $data;
        if (!$data) {
            date_default_timezone_set('Asia/Jakarta');
            return response()->json([
                'code' => 500,
                'message' => 'ERROR BRIDGING BPJS, cek Internet Atau Bpjs Down',
                'data' => $data
            ], 500);
        }



        $res['metadata'] = '';
        $res['result'] = 'Tidak ditemukan';

        $res['metadata'] =  $data['metadata'] ??  $data['metaData'];

        // if (!$data["response"]) {
        //     return $res;
        // }
        $nilairespon = $data["response"] ?? false;
        // return $nilairespon;
        if (!$nilairespon) {
            date_default_timezone_set('Asia/Jakarta');
            return $res;
        }
        $hasilakhir = self::decompress(self::stringDecrypt($kunci, $nilairespon));
        // return $hasilakhir;
        date_default_timezone_set('Asia/Jakarta');
        $res['result'] = json_decode($hasilakhir);
        if (!$hasilakhir) {
            return response()->json($data);
        }

        return $res;
    }

    public static function post_url(string $name, $param, $post)
    {
        $url = self::ws_url($name, $param);
        //$url = self::ws_url_dev($name, $param);

        $sign = self::getSignature($name);
        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];

        $header = $name === 'icare' ? self::getHeadericare($sign) : self::getHeader($sign);
        $response = Http::withHeaders($header)->post($url, $post);
        // return ($response);
        $data = json_decode($response, true);
        //return $data;
        if (!$data) {
            date_default_timezone_set('Asia/Jakarta');
            return response()->json([
                'code' => 500,
                'message' => 'ERROR BRIDGING BPJS, cek Internet Atau Bpjs Down'
            ], 500);
        }



        $res['metadata'] = '';
        $res['response'] = '';

        $res['metadata'] =  $data['metadata'] ??  $data['metaData'];

        $nilairespon = $data["response"] ?? false;

        if (!$nilairespon) {
            date_default_timezone_set('Asia/Jakarta');
            $res['response'] = 'Response Tidak ada';
            return $res;
        }
        $hasilakhir = self::decompress(self::stringDecrypt($kunci, $nilairespon));
        date_default_timezone_set('Asia/Jakarta');
        $res['response'] = json_decode($hasilakhir);

        return $res;
    }

    public static function put_url(string $name, $param, $post)
    {
        $url = self::ws_url($name, $param);
        // $url = self::ws_url_dev($name, $param);

        $sign = self::getSignature($name);
        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];

        $header = self::getHeader($sign);
        $response = Http::withHeaders($header)->put($url, $post);
        // return ($response);
        $data = json_decode($response, true);
        // return $data;
        if (!$data) {
            date_default_timezone_set('Asia/Jakarta');
            return response()->json([
                'code' => 500,
                'message' => 'ERROR BRIDGING BPJS, cek Internet Atau Bpjs Down'
            ], 500);
        }



        $res['metadata'] = '';
        $res['response'] = '';

        $res['metadata'] =  $data['metadata'] ??  $data['metaData'];
        $res['response'] =  $data['response'];

        $nilairespon = $data["response"] ?? false;
        if (!$nilairespon) {
            return $res;
        }
        $hasilakhir = self::decompress(self::stringDecrypt($kunci, $nilairespon));
        $res['result'] = json_decode($hasilakhir);
        if (!$hasilakhir) {
            return response()->json($data);
        }
        date_default_timezone_set('Asia/Jakarta');
        return $res;
    }
    public static function delete_url(string $name, $param, $post)
    {
        $url = self::ws_url($name, $param);
        // $url = self::ws_url_dev($name, $param);

        $sign = self::getSignature($name);
        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];

        $header = self::getHeader($sign);
        $response = Http::withHeaders($header)->delete($url, $post);
        // return ($response);
        $data = json_decode($response, true);
        // return $data;
        if (!$data) {
            date_default_timezone_set('Asia/Jakarta');
            return response()->json([
                'code' => 500,
                'message' => 'ERROR BRIDGING BPJS, cek Internet Atau Bpjs Down'
            ], 500);
        }



        $res['metadata'] = '';
        $res['response'] = '';

        $res['metadata'] =  $data['metadata'] ??  $data['metaData'];
        $res['response'] =  $data['response'];

        $nilairespon = $data["response"] ?? false;
        date_default_timezone_set('Asia/Jakarta');

        if (!$nilairespon) {
            return $res;
        }
        return $res;
    }


    public static function getSignature(string $name)
    {
        // BPJS_ANTREAN_CONS_ID=31014
        // BPJS_ANTREAN_SECRET=3sY5CB0658
        // $BPJS_ANTREAN_USER_KEY = '140dbebe0248aa4ce64557a8ffbdb0e9';
        // BPJS_ANTREAN_USER_KEY_DEV=f5abd04a8fadc1061e8853715662c3e8

        // $BPJS_ANTREAN_SECRET = '3sY5CB0658';
        $BPJS_ANTREAN_USER_KEY = 'f5abd04a8fadc1061e8853715662c3e8';


        $VCLAIM_DEV_USER_KEY_DEV = "fbad382d69383c78969f889077053ebb";
        $VCLAIM_DEV_USER_KEY = 'belum_ada';

        $cons = "31014";
        $secretKey = "3sY5CB0658";

        $USERKEY = $VCLAIM_DEV_USER_KEY_DEV;
        if ($name === 'vclaim') {
            $USERKEY = $VCLAIM_DEV_USER_KEY_DEV;
        } else if ($name === 'icare') {
            $USERKEY = $VCLAIM_DEV_USER_KEY_DEV;
        } else {
            $USERKEY = $BPJS_ANTREAN_USER_KEY;
        }

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $signature = hash_hmac('sha256', $cons . "&" . $tStamp, $secretKey, true);

        // base64 encode�
        $encodedSignature = base64_encode($signature);

        $data = array(
            'xconsid' => $cons,
            'xtimestamp' => $tStamp,
            'xsignature' => $encodedSignature,
            'user_key' => $USERKEY, // ini untuk vclaim
            // 'user_key' => $BPJS_ANTREAN_USER_KEY, // ini untuk antrean
            'secret_key' => $secretKey
        );

        return $data;
    }

    public static function getHeader($data)
    {
        // $data = self::getSignature();
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'Application/x-www-form-urlencoded',
            'X-cons-id' => $data['xconsid'],
            'X-timestamp' => $data['xtimestamp'],
            'X-signature' => $data['xsignature'],
            'user_key' => $data['user_key'],
        ];


        // return [
        //     'Accept' => 'application/json',
        //     'Content-Type' => 'application/json',
        //     'x-cons-id' => $data['xconsid'],
        //     'x-timestamp' => $data['xtimestamp'],
        //     'x-signature' => $data['xsignature'],
        //     'user_key' => $data['user_key'],
        // ];
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

    public static function metaData($code = 200, $msg = 'ok', $value = null)
    {
        $metadata = ['code' => $code, 'message' => $msg];
        $res['metadata'] = $metadata;
        $res['result'] = $value;

        return response()->json($res);
    }

    

    public static function getHeadericare($data)
    {
        // $data = self::getSignature();
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-cons-id' => $data['xconsid'],
            'X-timestamp' => $data['xtimestamp'],
            'X-signature' => $data['xsignature'],
            'user_key' => $data['user_key'],
        ];


        // return [
        //     'Accept' => 'application/json',
        //     'Content-Type' => 'application/json',
        //     'x-cons-id' => $data['xconsid'],
        //     'x-timestamp' => $data['xtimestamp'],
        //     'x-signature' => $data['xsignature'],
        //     'user_key' => $data['user_key'],
        // ];
    }
}
