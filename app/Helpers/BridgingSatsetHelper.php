<?php

namespace App\Helpers;

use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class BridgingSatsetHelper
{
    /**
     * wawan 
     */
    public static function root_url(){
        $url = 'https://api-satusehat.kemkes.go.id';
        
        return $url; 
    }

    public static function get_data_kfa($ext,$token, $params){

        
        // return self::root_url();
        $url = self::root_url() . $ext . $params;
        $response = Http::withToken($token)->get($url);
        $data = json_decode($response, true);

        
        return $data;
        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';

        $notfound = isset($data['entry']) ? count($data['entry']) === 0 : false;
        if ($notfound) {
            $error = true;
        }

        if ($error) {
            $err = [
                'method' => 'GET',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed' ,
                'data' => $resp
            ];
            return $send;
        }

        if ($data['resourceType'] === 'Bundle' && $data['total'] === 0) {
            $err = [
                'method' => 'GET',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);
            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }


        // JIKA SUCCESS
        $success = [
            'method' => 'GET',
            'url' => $params,
            'response' => $data,

        ];

        if ($data['resourceType'] === 'Bundle' && $data['total'] > 0) {
            $resp = Satset::firstOrCreate([
                'resource' => $data['resourceType'],
                'uuid' => $data['entry'][0]['resource']['id']
            ], $success);
            $send = [
                'message' => 'success',
                'data' => $resp
            ];
            return $send;
        }
    }
    /**
     * wawan 
     */

    public static function base_url()
    {
        $url_dev = 'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1';
        $url_staging = 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1';
        $url_prod = 'https://api-satusehat.kemkes.go.id/fhir-r4/v1';
        $client_id = '8Sy0DMwjAfINN24Wa22u0YcieLLc71bSmGkGqCFsDBcyhG1r';
        $client_secret = 'mj5cQtOjlkhGdK3nOl1YcGyAFx92WTWtALbdPJZIVMfFXDXGCSS6D35HZeWONwFJ';


        return $url_prod;
    }
    public static function organization_id()
    {
        $organization_id = '100026342';
        return $organization_id;
    }

    public static function get_data_nosave($token, $params)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->get($url);
        $data = json_decode($response, true);

        return $data;
        
    }

    public static function get_data($token, $params)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->get($url);
        $data = json_decode($response, true);

        // return $data;
        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';

        $notfound = isset($data['entry']) ? count($data['entry']) === 0 : false;
        if ($notfound) {
            $error = true;
        }

        if ($error) {
            $err = [
                'method' => 'GET',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed' ,
                'data' => $resp
            ];
            return $send;
        }

        if ($data['resourceType'] === 'Bundle' && $data['total'] === 0) {
            $err = [
                'method' => 'GET',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);
            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }


        // JIKA SUCCESS
        $success = [
            'method' => 'GET',
            'url' => $params,
            'response' => $data,

        ];

        if ($data['resourceType'] === 'Bundle' && $data['total'] > 0) {
            $resp = Satset::firstOrCreate([
                'resource' => $data['resourceType'],
                'uuid' => $data['entry'][0]['resource']['id']
            ], $success);
            $send = [
                'message' => 'success',
                'data' => $resp
            ];
            return $send;
        }
    }

    public static function post_data($token, $params, $form)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->post($url, $form);
        $data = json_decode($response, true);

        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';
        if ($error) {
            $err = [
                'method' => 'POST',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }

        // JIKA SUCCESS
        $success = [
            'method' => 'POST',
            'url' => $params,
            'response' => $data,

        ];
        $resp = Satset::firstOrCreate([
            'resource' => $data['resourceType'],
            'uuid' => $data['id']
        ], $success);
        $send = [
            'message' => 'success',
            'data' => $resp
        ];
        return $send;
    }

    public static function put_data($token, $params, $form)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->put($url, $form);
        $data = json_decode($response, true);

        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';
        if ($error) {
            $err = [
                'method' => 'PUT',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }

        // JIKA SUCCESS
        $success = [
            'method' => 'PUT',
            'url' => $params,
            'response' => $data,

        ];
        $resp = Satset::updateOrCreate([
            'resource' => $data['resourceType'],
            'uuid' => $data['id']
        ], $success);
        $send = [
            'message' => 'success',
            'data' => $resp
        ];
        return $send;
    }


    public static function post_bundle($token, $form, $noreg)
    {
        $url = self::base_url();
        $response = Http::withToken($token)->post($url, $form);
        $data = json_decode($response, true);
        // return $data;
        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';
        if ($error) {
            $err = [
                'method' => 'POST',
                'url' => $url,
                'response' => $data,
                'uuid' => $noreg
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }

        // JIKA SUCCESS
        $success = [
            'method' => 'POST',
            'url' => $url,
            'response' => $data,
        ];
        $resp = Satset::firstOrCreate([
            'resource' => $data['resourceType'],
            'uuid' => $noreg
        ], $success);
        $send = [
            'message' => 'success',
            'data' => $resp
        ];
        return $send;
    }
}
