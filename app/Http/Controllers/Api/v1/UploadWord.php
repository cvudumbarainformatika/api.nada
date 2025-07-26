<?php



namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
// use App\Models\Berita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Validation\Rule;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

class UploadWord extends Controller
{
    public function upload_word(Request $request)
    {
        // return new JsonResponse($request->doc);
        if ($request->hasFile('doc')) {

            $request->validate([
                'doc'=>'required'
            ]);

            $wordFile=IOFactory::load($request->doc);
            $html = new HTML($wordFile);
            $html->save('content.html');

            $content = readfile('content.html');

            Storage::delete('content.html');

            return response()->json(['result'=>$content]);
        }
        return new JsonResponse(['message'=>'Data tidak valid'], 500);
    }


}
