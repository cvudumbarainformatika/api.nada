<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    public static function responseGetSimplePaginate($raw, $req, $totalCount)
    {

        // $data = collect($raw)['data'];
        // $meta = collect($raw)->except('data');
        // return [
        //     'data' => $data,
        //     'meta' => $meta
        // ];
        $data = [
            'data' => $raw->items(),
            'meta' => [
                'first' => $raw->url(1),
                'last' => $raw->url(ceil($totalCount / $req['per_page'])),
                'prev' => $raw->previousPageUrl(),
                'next' => $raw->nextPageUrl(),
                'current_page' => $raw->currentPage(),
                'per_page' => (int)$req['per_page'],
                'total' => (int)$totalCount,
                'last_page' => ceil($totalCount / $req['per_page']),
                'from' => (($raw->currentPage() - 1) * $req['per_page']) + 1,
                'to' => min($raw->currentPage() * $req['per_page'], $totalCount),
            ],
        ];
        return $data;
    }
    public static function responseStore($data, $message = '',  $code = 200, $side = null)
    {

        return new JsonResponse([
            'data' => $data,
            'side' => $side,
            'message' => $message
        ], $code);
    }
}
