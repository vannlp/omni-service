<?php

namespace App\V1\Controllers;

use App\Supports\Log;
use App\Supports\TM_Error;
use App\Supports\Message;
use App\V1\Models\FileCloudModel;
use App\V1\Transformers\FileCloud\FileCloudListTransformer;
use App\V1\Transformers\FileCloud\FileCloudTransformer;
use App\V1\Validators\FileCloudCreateValidator;
use App\V1\Validators\FileCloudUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileCloudController extends BaseController
{
    const PATH = 'images/';

    public function __construct()
    {
        $this->model = new FileCloudModel();
    }

    public function search(Request $request, FileCloudListTransformer $transformer)
    {
        try {

            $input = $request->all();
            $limit = array_get($input, 'limit', 20);
            if (!empty($input['file_category_id'])) {
                $input['category'] = ['=' => "{$input['file_category_id']}"];
            }
            $result = $this->model->search($input, ['fileCategory', 'store'], $limit);
            Log::view($this->model->getTable());
            return $this->response->paginator($result, $transformer);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function store(Request $request, FileCloudCreateValidator $validator, FileCloudTransformer $transformer)
    {
        $input = $request->all();
        $validator->validate($input);
        unset($input['file']);

        try {
            DB::beginTransaction();
            $extension = $request->file->getClientOriginalExtension();
            $fileName = $this->removeAccented($input['title']) . '_' . time() . '.' . $extension;
            $input['url'] = env('AWS_URL') . self::PATH . $fileName;
            $input['path'] = self::PATH . $fileName;
            $result = $this->model->create($input);
            Log::create($this->model->getTable(), $result->title);
            DB::commit();

            Storage::disk('s3')->put($input['path'], file_get_contents($request->file));

            return $this->response->item($result, $transformer);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function update($id, Request $request, FileCloudUpdateValidator $validator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $validator->validate($input);
        unset($input['file'], $input['_method']);

        try {
            DB::beginTransaction();
            $file = $this->model->getFirstBy('id', $id);
            if ($request->has('file')) {
                $extension = $request->file->getClientOriginalExtension();
                $fileName = $this->removeAccented($input['title']) . '_' . time() . '.' . $extension;
                $input['url'] = env('AWS_URL') . self::PATH . $fileName;
                $input['path'] = self::PATH . $fileName;
            }
            $result = $this->model->update($input);
            Log::update($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();

            if (Storage::disk('s3')->exists($file->path)) {
                Storage::disk('s3')->delete($file->path);
            }

            if ($request->has('file')) {
                Storage::disk('s3')->put($input['path'], file_get_contents($request->file));
            }

            return ['status' => Message::get("folders.update-success", $result->title)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function delete($id)
    {
        try {
            $file = $this->model->getFirstBy('id', $id);
            if (!$file) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            DB::beginTransaction();
            $file->delete();
            Log::delete($this->model->getTable(), "#ID:" . $id);
            DB::commit();

            if (Storage::disk('s3')->exists($file->path)) {
                Storage::disk('s3')->delete($file->path);
            }

            return ['status' => Message::get("department.delete-success", $file->title)];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function removeAccented($str)
    {
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];

        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        $str = str_replace(' ', '_', $str);

        return strtolower($str);
    }
}