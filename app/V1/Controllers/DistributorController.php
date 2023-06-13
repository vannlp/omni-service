<?php


namespace App\V1\Controllers;


use App\Distributor;
use App\Exports\DistributorExport;
use App\Order;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Transformers\Distributor\DistributorTransformer;
use App\V1\Validators\DistributorAfterCreateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DistributorController extends BaseController
{
     /**
      * @var Distributor
      */
     protected $model;

     /**
      * DistributorController constructor.
      */
     public function __construct()
     {
          $this->model = new Distributor();
     }

     /**
      * @param Request $request
      * @return \Dingo\Api\Http\Response
      */
     public function search(Request $request)
     {
          $input  = $request->all();
          $result = $this->model->search($request);
          if (!empty($input['sort'])) {
               $this->sort($input, Distributor::class, $result);
          }
          $result = $result->paginate($request->get('limit', 20));
          return $this->response->paginator($result, new DistributorTransformer());
     }

     public function detail($id)
     {
          $result = $this->model->find($id);
          if (!$result) {
               return $this->responseError(Message::get('V003', "#$id"));
          }
          return $this->response->item($result, new DistributorTransformer());
     }

     public function update($id, Request $request)
     {
          try {
               $input  = $request->all();
               $result = $this->model->find($id);
               if (!$result) {
                    return $this->responseError(Message::get('V003', "#$id"));
               }
               DB::beginTransaction();
               $result->code               = Arr::get($input, 'code', $result->code);
               $result->name               = Arr::get($input, 'name', $result->name);
               $result->city_code          = Arr::get($input, 'city_code', $result->city_code);
               $result->city_full_name     = Arr::get($input, 'city_full_name', $result->city_full_name);
               $result->district_code      = Arr::get($input, 'district_code', $result->district_code);
               $result->district_full_name = Arr::get($input, 'district_full_name', $result->district_full_name);
               $result->ward_code          = Arr::get($input, 'ward_code', $result->ward_code);
               $result->ward_full_name     = Arr::get($input, 'ward_full_name', $result->ward_full_name);
               $result->value              = Arr::get($input, 'value', $result->value);
               $result->is_active          = Arr::get($input, 'is_active', $result->is_active);
               $result->save();
               DB::commit();
          } catch (\Exception $exception) {
               DB::rollBack();
               return $this->responseError($exception->getMessage());
          }
          return ['status' => Message::get('R002', $result->code)];
     }
     public function create(Request $request, DistributorAfterCreateValidator $afterCreateValidator)
     {
         $input = $request->all();
         $afterCreateValidator->validate($input);
         $check_distributor = Distributor::model()->where([
             'code'       => $input['code'],
             'city_code'  => $input['city_code'],
             'district_code'  => $input['district_code'],
             'ward_code'  => $input['ward_code'],
             'store_id'   => TM::getCurrentStoreId(),
             'company_id' => TM::getCurrentCompanyId()
         ])->first();
         if (!empty($check_distributor)) {
             return $this->responseError(Message::get('distributor.address-not-exist'));
         }
         try {
             DB::beginTransaction();
             $distributor = Distributor::create([
                 'code'               => Arr::get($input, 'code', null),
                 'name'               => Arr::get($input, 'name', null),
                 'city_code'          => Arr::get($input, 'city_code', null),
                 'city_full_name'     => Arr::get($input, 'city_full_name', null),
                 'district_code'      => Arr::get($input, 'district_code', null),
                 'district_full_name' => Arr::get($input, 'district_full_name', null),
                 'ward_code'          => Arr::get($input, 'ward_code', null),
                 'ward_full_name'     => Arr::get($input, 'ward_full_name', null),
                 'value'              => Arr::get($input, 'value', null),
                 'is_active'          => Arr::get($input, 'is_active', null),
                 'store_id'           => TM::getCurrentStoreId(),
                 'company_id'         => TM::getCurrentCompanyId()
             ]);
             DB::commit();
 
         } catch (\Exception $exception) {
             DB::rollBack();
             return $this->responseError($exception->getMessage());
         }
         return ['status' => Message::get('R001', $distributor->code)];
     }

     public function checkorder()
     {

           // gioi han so luong mua tren gio hang theo ttpp/npp
           $user = User::model()->where('id', TM::getCurrentCityCode())->first();
           $now          = date('Y-m-d');
           if (!empty($user)) {
               $orders = Order::model()->where('distributor_id', TM::getCurrentCityCode())->whereDate('created_at', $now)->get();
               if ($user->group_code == "DISTRIBUTOR") {
                   if (!empty($orders)) {
                       if (count($orders) >= $user->qty_max_day) {
                         return ["data" => ["check_order" => 1]];
                       }else{
                         return ["data" => ["check_order" => 0]];
                       }
                   }
               }
               if ($user->group_code == "HUB") {
                   $distributor_center = User::model()->where('id', $user->distributor_center_id)->first();
                   $orders_center = Order::model()->where('distributor_id', $user->distributor_center_id)->whereDate('created_at', $now)->get();
                   if (!empty($distributor_center) && !empty($orders_center)) {
                           if (count($orders) >= $distributor_center->qty_max_day) {
                              return ["data" => ["check_order" => 1]];
                           }else{
                              return ["data" => ["check_order" => 0]];
                           }
                   }
               }
           }
           return ["data" => ["check_order" => 0]];
           //end
     }

     /**
      * @param $id
      * @return \Illuminate\Http\JsonResponse
      */
     public function delete($id)
     {
          try {
               DB::beginTransaction();
               $result = $this->model->find($id);
               if (!$result) {
                    return $this->responseError(Message::get('V003', "#$id"));
               }
               $result->delete();
               DB::commit();
          } catch (\Exception $exception) {
               DB::rollBack();
               return $this->responseError($exception->getMessage());
          }
          return ['status' => Message::get('R003', $result->code)];
     }
     public function exportDistributors(Request $request)
     {
         //ob_end_clean();
          $input = $request->all();
          try {
               $date  = date('YmdHis', time());
               $distributor = Distributor::model()
                    ->select('name', 'code', 'city_code', 'city_full_name', 'district_code', 'district_full_name', 'ward_code', 'ward_full_name', 'is_active')
                    ->where('store_id', TM::getCurrentStoreId());
               if (isset($input['is_active'])) {
                    $distributor = $distributor->where('is_active', $input['is_active']);
               }
               if (isset($input['city_full_name'])) {
                    $distributor = $distributor->where('city_full_name', $input['city_full_name']);
               }
               if (isset($input['name'])) {
                    $distributor = $distributor->where('name', $input['name']);
               }
               if (isset($input['district_full_name'])) {
                    $distributor = $distributor->where('district_full_name', $input['district_full_name']);
               }
               if (isset($input['ward_full_name'])) {
                    $distributor = $distributor->where('ward_full_name', $input['ward_full_name']);
               }
               if (isset($input['code'])) {
                    $distributor = $distributor->where('code', $input['code']);
               }
               $distributors = $distributor->get()->toArray();
               //ob_start();
               return Excel::download(new DistributorExport($distributors), 'distributor_' . $date . '.xlsx');
          } catch (\Exception $ex) {
               DB::rollBack();
               $response = TM_Error::handle($ex);
               return $this->response->errorBadRequest($response["message"]);
          }
     }
}
