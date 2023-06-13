<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:28 PM
 */

namespace App\V1\Controllers;

use App\RotationResult;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\RotationResultModel;
use App\V1\Transformers\RotationResult\RotationResultTransformer;
use App\V1\Validators\RotationResult\RotationResultCreateValidator;
use App\V1\Validators\RotationResult\RotationResultUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RotationResultController extends BaseController
{

     protected $rotationResultModel;

     public function __construct()
     {
          $this->rotationResultModel = new RotationResultModel();
     }

     public function search(Request $request, RotationResultTransformer $transformer)
     {
          $input = $request->all();
          $limit = array_get($input, 'limit', 20);
          $result = $this->rotationResultModel->search($input, [], $limit);

          return $this->response->paginator($result, $transformer);
     }

     public function view($id, RotationResultTransformer $transformer)
     {
          $result = RotationResult::model()->where(['id' => $id])->first();
          if (!$result) {
               return ["data" => null];
          }
          return $this->response->item($result, $transformer);
     }

     public function create(Request $request, RotationResultCreateValidator $createValidator)
     {
          $input = $request->all();
          $createValidator->validate($input);

          try {
               if (!empty($input['code'])) {
                    $result = RotationResult::model()->where('code', $input['code'])->first();
                    if (!empty($result)) {
                         return $this->responseError(Message::get('V008', Message::get($result->code)));
                    }
               }
               DB::beginTransaction();
               $rotationResult = $this->rotationResultModel->upsert($input);

               DB::commit();
          } catch (\Exception $ex) {
               DB::rollBack();
               $response = TM_Error::handle($ex);
               return $this->response->errorBadRequest($response['message']);
          }

          return ['status' => Message::get("R001", $rotationResult->code)];
     }

     public function update($id, Request $request, RotationResultUpdateValidator $updateValidator)
     {
          $input = $request->all();
          $input['id'] = $id;
          $updateValidator->validate($input);

          try {
               DB::beginTransaction();

               $rotationResult = $this->rotationResultModel->upsert($input);

               DB::commit();
          } catch (\Exception $ex) {
               DB::rollBack();
               $response = TM_Error::handle($ex);
               return $this->response->errorBadRequest($response['message']);
          }

          return ['status' => Message::get("R002", $rotationResult->code)];
     }

     public function delete($id)
     {
          try {
               $result = RotationResult::find($id);
               if (empty($result)) {
                    return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
               }
               $result->delete();
          } catch (\Exception $ex) {
               $response = TM_Error::handle($ex);
               return $this->response->errorBadRequest($response['message']);
          }

          return ['status' => Message::get("R003", $result->code)];
     }
}
