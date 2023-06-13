<?php

namespace App\V1\Controllers;

use App\Exports\ProductHubExport;
use App\ProductHub;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\Order;
use App\OrderDetail;
use App\V1\Models\ProductHubModel;
use App\V1\Transformers\ProductHub\ProductHubTransformer;
use App\V1\Validators\ProductHub\ProductHubUpsertValidator;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ProductHubController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new  ProductHubModel();
    }

    public function search(Request $request)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        if(!empty($input['user_id'])){
            $input['user_id']    = $input['user_id'];
        }
        $result              = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, new ProductHubTransformer());
    }
    public function searchDetail(Request $request)
    {
        $input               = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        $product = ProductHub::model()->where('company_id', $input['company_id']);
        if(!empty($input['user_id'])){
            $product = $product->where('user_id', $input['user_id'])->get();
        }
        if(empty($input['user_id'])){
            return ['data' => []];
        }
        $proHub2 =[];


        foreach ($product as $productHub){

            $now          = date('Y-m-d');
            $orders = Order::model()->with('details')->where('distributor_id', $productHub->user_id)->whereDate('created_at', $now)->get();
            foreach ($orders as $order) {
                $order_detail = OrderDetail::model()->where('product_code', $productHub->product_code)->where('order_id', $order->id)->sum('qty');
            }
            if(!empty($order_detail)){
                $order_detail > $productHub->limit_date ? $qty_re = 0 : $qty_re = $productHub->limit_date - $order_detail;
            }
            $proHub = [
                'id'            => $productHub->id,
                'product_id'    => $productHub->product_id,
                'product_code'  => $productHub->product_code,
                'product_name'  => $productHub->product_name,
                'unit_id'       => $productHub->unit_id,
                'unit_name'     => $productHub->unit_name,
                'limit_date'    => $productHub->limit_date,
                'qty_re'        => $qty_re ?? 0,
            ];
            array_push($proHub2, $proHub);
            $user = $productHub->user_id;
        }
        if(!empty($user)){
            return response()->json(['data' =>['products' => $proHub2, 'user_id'=>$user]]);
        }
        if(empty($user)){
            return ['data' => []];;
        }
    }

    public function detail($id)
    {
        $result = ProductHub::find($id);
        if (empty($result)) {
            return ['data' => ''];
        }
        return $this->response->item($result, new ProductHubTransformer());
    }

    public function create(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('Cập nhật sản phẩm cho nhà phân phối thành công')]);
    }

    public function update($id, Request $request, ProductHubUpsertValidator $productHubUpsertValidator)
    {
        $input          = $request->all();
        $input['id']    = $id;
        $productHubUpsertValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->product_name)]);
    }

    public function delete($id)
    {
        try {
            $result = ProductHub::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->product_name)]);
    }


    public function exportProductHub(Request $request)
    {
        $input               = $request->all();
        $date  = date('YmdHis', time());
        $input['company_id'] = TM::getCurrentCompanyId();
        $product = ProductHub::model()->where('company_id', $input['company_id']);
        if(!empty($input['user_id'])){
            $product = $product->where('user_id', $input['user_id'])->get();
        }
        if(empty($input['user_id'])){
            return ['data' => []];
        }
        $proHub2 =[];


        foreach ($product as $productHub){

            $now          = date('Y-m-d');
            $orders = Order::model()->with('details')->where('distributor_id', $productHub->user_id)->whereDate('created_at', $now)->get();
            foreach ($orders as $order) {
                $order_detail = OrderDetail::model()->where('product_code', $productHub->product_code)->where('order_id', $order->id)->sum('qty');
            }
            if(!empty($order_detail)){
                $order_detail > $productHub->limit_date ? $qty_re = 0 : $qty_re = $productHub->limit_date - $order_detail;
            }
            $proHub = [
                'id'            => $productHub->id,
                'product_id'    => $productHub->product_id,
                'product_code'  => $productHub->product_code,
                'product_name'  => $productHub->product_name,
                'unit_id'       => $productHub->unit_id,
                'unit_name'     => $productHub->unit_name,
                'limit_date'    => $productHub->limit_date,
                'qty_re'        => $qty_re ?? 0,
            ];
            array_push($proHub2, $proHub);
            $user = $productHub->user_id;
        }
        if(!empty($user)){
            // return response()->json(['data' =>['products' => $proHub2, 'user_id'=>$user]]);
            return Excel::download(new ProductHubExport($proHub2), 'ProductHub_' . $date . '.xlsx');
        }
        if(empty($user)){
            return ['data' => []];;
        }
    }
}
