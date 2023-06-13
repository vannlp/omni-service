<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:51 AM
 */

namespace App\V1\Controllers;


use App\Region;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\RegionModel;
use App\V1\Transformers\Region\RegionTransformer;
use App\V1\Validators\Region\RegionCreateValidator;
use App\V1\Validators\Region\RegionUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionController extends BaseController
{
     protected $model;

     /**
      * RegionController constructor.
      */
     public function __construct()
     {
          $this->model = new RegionModel();
     }

     public function search(Request $request,  RegionTransformer $regionTransformer)
     {

          $input = $request->all();
          $limit = array_get($input, 'limit', 20);
          $result = $this->model->search($input, [], $limit);

          return $this->response->paginator($result, $regionTransformer);
     }

     /**
      * @param $id
      * @param RegionTransformer $regionTransformer
      *
      * @return \Dingo\Api\Http\Response
      */
     public function detail($id, RegionTransformer $regionTransformer)
     {
          $result = Region::model()->where(['id' => $id])->first();
          if (!$result) {
               return ["data" => null];
          }
          Log::view($this->model->getTable());
          return $this->response->item($result, $regionTransformer);
     }

     public function create(Request $request, RegionCreateValidator $createValidator)
     {
          $input = $request->all();
         $createValidator->validate($input);
         // print_r($input);die;
          try {
               DB::beginTransaction();
               $region = $this->model->upsert($input);
               DB::commit();
          } catch (\Exception $ex) {
               DB::rollBack();
               $response = TM_Error::handle($ex);
               return $this->response->errorBadRequest($response['message']);
          }

          return ['status' => Message::get("R001", $region->code)];
     }

     public function update($id, Request $request, RegionUpdateValidator $regionUpdateValidator)
     {
          $input = $request->all();
         $input['id'] = $id;
         $regionUpdateValidator->validate($input);

          try {
               DB::beginTransaction();

               $region = $this->model->upsert($input);

               DB::commit();
          } catch (\Exception $ex) {
               DB::rollBack();
               $response = TM_Error::handle($ex);
               return $this->response->errorBadRequest($response['message']);
          }

          return ['status' => Message::get("R002", $region->code)];
     }

     public function delete($id)
     {
          try {
               $result = Region::find($id);
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
