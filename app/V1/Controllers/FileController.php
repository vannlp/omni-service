<?php


namespace App\V1\Controllers;

use App\Company;
use App\File;
use App\Supports\Log;
use App\Supports\TM_Error;
use App\Supports\Message;
use App\TM;
use App\V1\Validators\FileUpload\FileUploadValidator;
use App\V1\Models\FileModel;
use App\V1\Transformers\File\FileTransformer;
use App\V1\Validators\FilesUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class FileController extends BaseController
{
    /**
     * @var \App\V1\Models\FileModel
     */
    protected $model;
    /**
     * @var Client
     */
    protected $client;

    /**
     * FileController constructor.
     */
    public function __construct()
    {
        $this->model  = new FileModel();
        $this->client = new Client();
    }

    /**
     * @param Request $request
     * @param FileTransformer $fileTransformer
     * @return \Dingo\Api\Http\Response
     */

    public function search(Request $request, FileTransformer $fileTransformer)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result              = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $fileTransformer);
    }

    public function detail($id, FileTransformer $fileTransformer)
    {
        $result = File::find($id);
        if (empty($result)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $fileTransformer);
    }


    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = File::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID #$id"), 400);
            }
            // 1. Delete file in source
            $result->delete();
            // 2. Delete file server
            $endpoint = env('GET_FILE_URL');
            $this->client->request('delete', $endpoint . $result->code);
            Log::delete('files', $result->file_name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message'], 400);
        }
        return ['status' => Message::get("R003", $result->file_name)];
    }

    /**
     * @param Request $request
     * @param FileUploadValidator $fileUploadValidator
     * @return array|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
//    public function upload(Request $request, FileUploadValidator $fileUploadValidator)
//    {
//        ini_set('max_execution_time', 1000);
//        ini_set('memory_limit', '50M');
//        ini_set('upload_max_filesize', '50M');
//        ini_set('post_max_size', '50M');
//        $input = $request->all();
//        $fileUploadValidator->validate($input);
//        try {
//            $company = Company::find(TM::getCurrentCompanyId());
//            $path    = $company->name ?? 'Unknown';
//            $path    = Str::slug(Str::ascii($path));
//            if (!$request->hasFile('file')) {
//                return $this->responseError(Message::get("V001", Message::get('files')));
//            }
//            $file     = $request->file('file');
//            $fileSize = $file->getSize();
//            if ($fileSize > 52428800) { //Upto  ~50MB
//                return $this->responseError(Message::get("file_size_upload_error"));
//            }
//            $mimeType        = $file->getClientMimeType();
//            $filenameWithExt = $file->getClientOriginalName();
//            $fileName        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
//            $fileName        = Str::slug(Str::ascii($fileName)) . date('dmYHis');
//            $extension       = $file->getClientOriginalExtension();
//            $fileinfo        = pathinfo($file);
//            if (!file_exists($dir = storage_path('uploads/'))) {
//                mkdir($dir, 0777, true);
//                chmod($dir, 0777);
//            }
//            $filePath = realpath(storage_path('uploads'));
//            $file->move($filePath, $fileName . "." . $extension);
//            $fileLocation = $filePath . "/" . $fileName . "." . $extension;
//            $url          = env('UPLOAD_URL');
//            $endpoint     = "/upload/$path";
//
//            $response = $this->client->request('post', $url . $endpoint, [
//                'multipart' => [
//                    [
//                        'name'         => $filenameWithExt,
//                        'FileContents' => fopen($fileLocation, 'r'),
//                        'contents'     => fopen($fileLocation, 'r'),
//                        'FileInfo'     => json_encode($fileinfo),
//                        'headers'      => [
//                            'Content-Type'        => $mimeType,
//                            'Content-Disposition' => 'form-data; name="FileContents"; filename="' . $filenameWithExt . '"',
//                        ],
//                    ]
//                ],
//            ]);
//
//            $result = json_decode($response->getBody()->getContents(), true);
//
//            $param     = [
//                'code'       => $result['id'],
//                'file_name'  => $fileName . "." . $extension,
//                'title'      => $filenameWithExt,
//                'extension'  => $extension,
//                'size'       => $fileSize,
//                'type'       => MIME_FILE_TYPE[$extension] ?? null,
//                'version'    => 1,
//                'is_active'  => 1,
//                'company_id' => TM::getCurrentCompanyId(),
//                'store_id'   => TM::getCurrentStoreId(),
//            ];
//
//            $fileModel = new FileModel();
//            $fileModel->create($param);
//            unlink($fileLocation);
//            return ['status'   => Message::get("file.upload-success", $filenameWithExt),
//                    'response' => $result,
//                    'data'     => env('GET_FILE_URL') . $result['id']
//            ];
//        }
//        catch (\Exception $ex) {
//            $response = TM_Error::handle($ex);
//            return $this->responseError($response['message'], 400);
//        }
//    }
    public function upload(Request $request, FileUploadValidator $fileUploadValidator)
    {
        $input = $request->all();
        $fileUploadValidator->validate($input);
        try {
            $file    = explode(".", $input['name']);
            $fileTmp = $file;
            unset($fileTmp[count($fileTmp) - 1]);
            $param = [
                'code'       => $input['id'],
                'file_name'  => $input['name'],
                'title'      => implode(".", $fileTmp),
                'extension'  => $ex = end($file),
                'type'       => MIME_FILE_TYPE[$ex] ?? null,
                'size'       => $input['size'],
                'version'    => 1,
                'is_active'  => 1,
                'company_id' => TM::getCurrentCompanyId(),
                'store_id'   => TM::getCurrentStoreId(),
            ];
            $this->model->create($param);
            return ['status' => Message::get("file.upload-success", $input['name'])];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }
}