<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Paymentbankjatim;
use App\Models\Simrs\Kasir\Pembayarannontunai;
use App\Models\Simrs\Kasir\Tagihannontunai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankjatiminsertController extends Controller
{
    public function insertqrisbayar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billNumber' => 'required',
            'purposetrx' => 'required',
            'storelabel' => 'required',
            'customerlabel' => 'required',
            'terminalUser' => 'required',
            'amount' => 'required',
            'core_reference' => 'required',
            'customerPan' => 'required',
            'merchantPan' => 'required',
            'invoice_number' => 'required',
            'transactionDate' => 'required'
        ]);

        if ($validator->fails()) {
            $response = [
                'responsCode' => '201',
                'responsDesc' => 'Missing some required data',
                'error' => $validator->errors(),
            ];
            return new JsonResponse($response, 200);
            // return response()->json($validator->errors(), 422);
        }
        $simpanpayment = Paymentbankjatim::firstOrCreate(
            [
                'billNumber' => $request->billNumber
            ],
            [
                'purposetrx' => $request->purposetrx,
                'storelabel' => $request->storelabel,
                'customerlabel' => $request->customerlabel,
                'terminalUser' => $request->terminalUser,
                'amount' => $request->amount,
                'core_reference' => $request->core_reference,
                'customerPan' => $request->customerPan,
                'merchantPan' => $request->merchantPan,
                'pjsp' => $request->pjsp,
                'invoice_number' => $request->invoice_number,
                'transactionDate' => $request->transactionDate
            ]
        );
        if (!$simpanpayment) {
            $response = [
                'responsCode' => '201',
                'responsDesc' => 'Data Gagal Disimpan...!!!'
            ];
            return new JsonResponse($response, 200);
        }

        $simpanpembayaran = Pembayarannontunai::firstOrCreate(
            [
                'rs1' => $request->billNumber
            ],
            [
                'rs2' => $request->transactionDate,
                'rs3' => $request->amount,
                'rs6' => 'QRIS'
            ]
        );
        if (!$simpanpembayaran) {
            $response = [
                'responsCode' => '201',
                'responsDesc' => 'Data Gagal Disimpan...!!!'
            ];
            return new JsonResponse($response, 200);
        }
        $updatekunjungan = Tagihannontunai::where('rs17', $request->invoice_number)->first();
        $updatekunjungan->rs12 = '2';
        $updatekunjungan->save();

        if ($simpanpayment->wasRecentlyCreated) {
            $response = $simpanpayment;
            $response->responsCode = '00';
            $response->responsDesc = 'Success';
            unset($response->created_at);
            unset($response->updated_at);
            return new JsonResponse($response, 200);
        } else {
            $response = $simpanpayment;
            $response->responsCode = '201';
            $response->responsDesc = 'Data already exist';
            unset($response->created_at);
            unset($response->updated_at);
            return new JsonResponse($response, 200);
        }
    }
}
