<?php

namespace App\Helpers;

use App\Models\Satset\SatsetToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class AuthSatsetHelper
{

    public static function guzzleToken()
    {
        $url_dev = 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_staging = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_prod = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';


        $client_id = '8Sy0DMwjAfINN24Wa22u0YcieLLc71bSmGkGqCFsDBcyhG1r';
        $client_secret = 'mj5cQtOjlkhGdK3nOl1YcGyAFx92WTWtALbdPJZIVMfFXDXGCSS6D35HZeWONwFJ';

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $options = [
            'headers' => $headers,
            'form_params' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret
            ]
        ];

        $client = new Client();
        $request = $client->post($url_prod, $options);
        return $request;
    }
    public static function getToken()
    {
        $url_dev = 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_staging = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_prod = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';

        $client_id = '9wvWiOVMkOTBAixmVqufdAK5MVQ7aXRLDRG8f70abw7nDXFp';
        $client_secret = 'GNxqqQJGim11FAr3243vWMLpgAUxBsPOGik0wBhkVH3BbrpAXuz6uz8MkvsQ0vzF';

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];



        $response = Http::withHeaders($headers)
            ->asForm()->post($url_prod, [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ]);
        return $response;
    }

    public static function accessToken()
    {
        $cekToken = SatsetToken::find(1);
        if (!$cekToken) {
            return self::getSaveToken();
        }

        $expireIn = $cekToken->expires_in; // dlm detik
        $waktu = Carbon::createFromFormat('Y-m-d H:i:s', $cekToken->updated_at)->addSeconds($expireIn);
        $diff = now()->diffInSeconds($waktu, false);

        // ambil token baru
        if ($diff < 50) {
            $ambilToken = self::getToken();
            $cekToken->update([
                'token' => $ambilToken['access_token'],
                'api_product_list' => $ambilToken['api_product_list'],
                'application_name' => $ambilToken['application_name'],
                'client_id' => $ambilToken['client_id'],
                'developer_email' => $ambilToken['developer.email'],
                'expires_in' => $ambilToken['expires_in'],
                'organization_name' => $ambilToken['organization_name']
            ]);

            $token = $ambilToken['access_token'];
            return $token;
        } 

        // return token lama
        $token = $cekToken->token;
        return $token;
    }

    public static function getSaveToken()
    {
        $ambilToken = self::getToken();
        SatsetToken::create(
            [
                'token' => $ambilToken['access_token'],
                'api_product_list' => $ambilToken['api_product_list'],
                'application_name' => $ambilToken['application_name'],
                'client_id' => $ambilToken['client_id'],
                'developer_email' => $ambilToken['developer.email'],
                'expires_in' => $ambilToken['expires_in'],
                'organization_name' => $ambilToken['organization_name'],
            ]
        );

        $token = $ambilToken['access_token'];
        return $token;
    }
}
