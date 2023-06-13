<?php


namespace App\V1\Controllers;


use App\Cart;
use App\Coupon;
use App\CouponCategory;
use App\CouponHistory;
use App\CouponProduct;
use App\Promocode;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\PromocodeModel;
use App\V1\Transformers\Promocode\PromocodeTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PromocodeController extends BaseController
{
    /**
     * @var PromocodeModel
     */
    protected $model;

    /**
     * CouponController constructor.
     */
    public function __construct()
    {
        $this->model        = new PromocodeModel();
    }

    public function search(Request $request, PromocodeTransformer $promocodeTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $promocodeTransformer);
    }

    public function create(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $result->name);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.create-success", $result->code)];
    }

    public function update($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $result->id, null, $result->name);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.update-success", $result->code)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = Promocode::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $result->delete();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.delete-success", $result->code)];
    }

   
}