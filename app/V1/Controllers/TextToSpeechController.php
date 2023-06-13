<?php

namespace App\V1\Controllers;

use App\Supports\Message;
use App\Supports\TM_Error;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TextToSpeechController extends BaseController
{
    protected $voices;
    public function __construct()
    {
        $this->voices = [
            "doanngocle"         => "Ngọc Lê - Nữ miền Bắc",
            "phamtienquan"       => "Tiến Quân - Nam miền Bắc",
            "lethiyen"           => "Lê Yến - Nữ miền Nam",
            "nguyenthithuyduyen" => "Thùy Duyên - Nữ miền Nam",
            "hn-quynhanh"        => "Quỳnh Anh - Nữ miền Bắc",
            "hue-maingoc"        => "Mai Ngọc - Nữ miền Trung",
            "hue-baoquoc"        => "Bảo Quốc - Nam miền Trung",
            "hcm-minhquan"       => "Minh Quân - Nam miền Nam",
            "hn-thanhtung"       => "Thanh Tùng - Nam miền Bắc",
            "hcm-diemmy"         => "Diễm My - Nữ miền Nam",
        ];
    }
    public function textToSpeech(Request $request)
    {
        try {
            $input = $request->all();
            if (empty($input['text'])) {
                return $this->responseError(Message::get('V001', Message::get('text')));
            }

            $audio_type = ['streaming' => 1, 'wav' => 2, 'mp3' => 3];

            $param = [
                "text"              => $input['text'],
                "voice"             => $input['voice'] ?? env("TEXT_SPEECH_VOICE_DEFAULT"),
                "id"                => $input['audio_key'] ?? 1,
                "without_filter"    => true,
                "speed"             => 0,
                "tts_return_option" => $audio_type['mp3'],
            ];
            $client = new Client([
                'headers' => [
                    'token'        => env('TEXT_SPEECH_KEY'),
                    'Content-type' => 'application/json'
                ],
                'verify' => false
            ]);
            $response = $client->post(env("TEXT_SPEECH_ENDPOINT"), ['json' => $param]);
            $response = $response->getBody()->getContents() ?? null;
            // $response = !empty($response) ? json_decode($response, true) : [];

            $file = storage_path("ResultSound.mp3");
            file_put_contents($file, $response);
            $client = new Client([
                'headers'      => [
                    'Content-Type'        => 'audio/wav',
                    'Content-Disposition' => 'attachment; form-data; name="ResultSound.mp3"; filename="ResultSound.mp3"',
                ],
                'verify' => false
            ]);
            $response = $client->post(env("UPLOAD_URL") . "/upload/kpis", [
                'multipart' => [
                    [
                        'name'         => "ResultSound.mp3",
                        'contents'     => fopen($file, 'r')
                    ]
                ],
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            unlink($file);
            return response()->json(['data' => $result, 'url' => env('GET_FILE_URL') . $result['id']]);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
    }
}
