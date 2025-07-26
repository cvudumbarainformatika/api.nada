<?php

namespace App\Http\Controllers\Api\Anjungan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnjunganController extends Controller
{

    public function bridgingbpjs(){
        $uid = "31014";
        eval(base64_decode('CSRzZWNyZXQgPSAiM3NZNUNCMDY1OCI7DQoJJHVzZXJfa2V5ID0gIjdlMzk4ZjRmNjIyNTJmMTQxNDMxYmVjZDdkYTZlOTg2Ijs='));
        // eval(base64_decode('CSRzZWNyZXQgPSAiM3NZNUNCMDY1OCI7DQoJJHVzZXJfa2V5ID0gImZiYWQzODJkNjkzODNjNzg5NjlmODg5MDc3MDUzZWJiIjs=='));
        $timestmp = time();
        $str = $uid."&".$timestmp;
        // $url_header="https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev/";
        $url_header="https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/";
        $signature = base64_encode(hash_hmac('sha256', utf8_encode($str), utf8_decode($secret), TRUE));
    }

	public function decompress($string){
		return \LZCompressor\LZString::decompressFromEncodedURIComponent($string);
	}

    public function decoding($string){
        return json_decode(decompress(stringDecrypt($string)));
    }

    public function request($url, $hashsignature, $uid, $timestmp, $method='', $myvars=''){
		$conn = $GLOBALS['conn'];
		$session = curl_init($url);
		$arrheader =  array(
			'X-cons-id: '.$uid,
			'X-timestamp: '.$timestmp,
			'X-signature: '.$hashsignature,
			'user_key: '.$GLOBALS['user_key']
		);

		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPHEADER, $arrheader);
		curl_setopt($session, CURLOPT_VERBOSE, true);
		//curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

		if($method == 'POST'){
			curl_setopt($session, CURLOPT_POST, true );
			curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
			//curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		}

		if($method == 'PUT'){
			curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
			//curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		}

		if($method == 'GET'){
			curl_setopt($session, CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
			//curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		}
		if($method == 'DELETE'){
			curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
			//curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		}

		curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($session);
		$resDecode = json_decode($response);
		if($resDecode->metaData->code !== "200"){
			$conn->query("
				insert into bpjs_http_respon(
					method,
					request,
					respon,
					url,
					tgl
				)values(
					'$method',
					'$myvars',
					'$response',
					'$url',
					now()
			);");
			return $response;
		}
		$resDecode->response = decoding($resDecode->response);
		$responseEncode = json_encode($resDecode);
		$conn->query("
			insert into bpjs_http_respon(
				method,
				request,
				respon,
				url,
				tgl
			)values(
				'$method',
				'$myvars',
				'$responseEncode',
				'$url',
				now()
		);");
		return $responseEncode;
	}
}
