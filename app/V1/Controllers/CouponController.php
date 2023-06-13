<?php


namespace App\V1\Controllers;


use App\Cart;
use App\Coupon;
use App\CouponCategory;
use App\CouponCategoryexcept;
use App\CouponCode;
use App\CouponCodes;
use App\CouponHistory;
use App\CouponProduct;
use App\CouponProductexcept;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\CouponModel;
use App\V1\Models\CouponCodesModel;
use App\V1\Transformers\Coupon\CouponApplied;
use App\V1\Transformers\Coupon\CouponCodeTransformer;
use App\V1\Transformers\Coupon\CouponHistoryTransformer;
use App\V1\Transformers\Coupon\CouponTransformer;
use App\V1\Validators\CouponCreateValidator;
use App\V1\Validators\CouponUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class CouponController extends BaseController
{
    /**
     * @var CouponModel
     */
    protected $model;
    protected $codeModel;
    protected $historyModel;
    protected $couponCodesModel;
    /**
     * CouponController constructor.
     */
    public function __construct()
    {
        $this->model        = new CouponModel();
        $this->codeModel = new CouponCodes();
        $this->couponCodesModel = new CouponCodesModel();
        $this->historyModel = new CouponHistory();
    }

    /**
     * @param Request $request
     * @param CouponTransformer $couponTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, CouponTransformer $couponTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        // if (!empty($input['name'])) {
        //     $input['name'] = ['like' => $input['name']];
        // }
        // if (!empty($input['code'])) {
        //     $input['code'] = ['like' => $input['code']];
        // }
        // if (!empty($input['type'])) {
        //     $input['type'] = ['like' => $input['type']];
        // }
        Log::view($this->model->getTable());
        $result = $this->model->searchs($input, ['coupon'], $limit);
        return $this->response->paginator($result, $couponTransformer);
    }

    /**
     * @param $id
     * @param CouponTransformer $couponTransformer
     * @return array[]|\Dingo\Api\Http\Response
     */
    public function detail($id, CouponTransformer $couponTransformer)
    {
        $result = Coupon::find($id);
        if (empty($result)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $couponTransformer);
    }

    /**
     * @param Request $request
     * @param CouponCreateValidator $couponCreateValidator
     * @return array|void
     */
    public function create(Request $request, CouponCreateValidator $couponCreateValidator)
    {
        $input = $request->all();
        $couponCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $couponCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.create-success", $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param CouponUpdateValidator $couponUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, CouponUpdateValidator $couponUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $couponUpdateValidator->validate($input);
        if (!empty($input['name'])) {
            $input['name'] = str_clean_special_characters($input['name']);
        }
        if (!empty($input['code'])) {
            $input['code'] = str_clean_special_characters($input['code']);
        }
        $couponUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            if (empty($result->id) && $result['status_code'] == 400) {
                return response()->json(['message' => $result['message']], 400);
            }
            Log::update($this->model->getTable(), "#ID:" . $result->id, null, $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.update-success", $result->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = Coupon::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            # Check Coupon Product
            CouponProduct::model()->where('coupon_id', $id)->delete();
            # Check Coupon Category
            CouponCategory::model()->where('coupon_id', $id)->delete();
            # Check Coupon Product Except
            CouponProductexcept::model()->where('coupon_id', $id)->delete();
            # Check Coupon Category Except
            CouponCategoryexcept::model()->where('coupon_id', $id)->delete();
            Log::delete($this->model->getTable(), "#ID:" . $result->id . "-" . $result->name);
            $result->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.delete-success", $result->name)];
    }

    public function clientGetCoupon(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        if (empty($input['cart_id'])) {
            $this->responseError(Message::get("V001", Message::get("cart_id")));
        }
        $now   = date('Y-m-d H:i:s', time());
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['type'])) {
            $input['type'] = ['like' => $input['type']];
        }
        $input['store_id']   = $store_id;
        $input['company_id'] = $company_id;
        $input['date_start'] = ['<=' => $now];
        $input['date_end']   = ['>=' => $now];
        $input['status']     = ['=' => 1];
        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        $cart = Cart::with('details.product')->where('id', $input['cart_id'])->first();
        return $this->response->paginator($result, new CouponApplied($cart));
    }

    public function adminGetCoupon(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        if (empty($input['cart_id'])) {
            $this->responseError(Message::get("V001", Message::get("cart_id")));
        }
        $now   = date('Y-m-d H:i:s', time());
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['type'])) {
            $input['type'] = ['like' => $input['type']];
        }
        $input['store_id']   = $store_id;
        $input['company_id'] = $company_id;
        $input['date_start'] = ['<=' => $now];
        $input['date_end']   = ['>=' => $now];
        $input['status']     = ['=' => 1];
        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        $cart = Cart::with('details.product')->where('id', $input['cart_id'])->first();
        return $this->response->paginator($result, new CouponApplied($cart));
    }

    public function clientGetCouponDetail($id, Request $request, CouponTransformer $couponTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['type'])) {
            $input['type'] = ['like' => $input['type']];
        }
        $input['id']         = $id;
        $input['store_id']   = $store_id;
        $input['company_id'] = $company_id;
        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $couponTransformer);
    }

    public function couponHistory(Request $request)
    {
        $result = $this->historyModel->with(['order', 'user', 'coupon'])->search($request)
            ->paginate($request->get('limit', 20));
        return $this->response->paginator($result, new CouponHistoryTransformer());
    }
    public function couponHistoryDetail($code, Request $request)
    {
        $result = $this->historyModel->with(['order', 'user', 'coupon'])->search($request)
            ->where('coupon_code', $code)
            ->paginate($request->get('limit', 20));
        return $this->response->paginator($result, new CouponHistoryTransformer());
    }

    public function GetListCouponCode($id, Request $request, CouponCodeTransformer $CouponCodeTransformer)
    {
        $result = $this->codeModel->where('coupon_id', $id)->with('user')
            ->paginate($request->get('limit', 20));
        return $this->response->paginator($result, new CouponCodeTransformer());
    }

    public function searchCouponCodeDetail($id, Request $request, CouponCodeTransformer $CouponCodeTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['id'] = $id;
        $result = $this->couponCodesModel->searchDetail($input, [], $limit);
        return $this->response->paginator($result, new CouponCodeTransformer());
    }
    public function deletecodeCoupon($id, Request $request)
    {
        $code = CouponCodes::find($id);
        $check = CouponCodes::model()->where('id', $id)->first();
        if (!empty($check->order_used) || $check->is_active == 1) {
            return $this->responseError(Message::get("V1002", 'Mã giảm giá'), 400);
        }
        $code->delete();
        return ['status' => Message::get("coupons.delete-success", $id)];
    }

    public function updateStatus($id, Request $request)
    {
        $input = $request->all();
        $coupon = Coupon::find($id);
        $coupon->status = $input['status'];
        $coupon->save();
        return ['status' => Message::get("coupons.update-success", $coupon->name)];
    }
    public function deleteAllCouponById(Request $request)
    {
        $input = $request->all();
        $coupon_code = array_get($input, 'coupon', []);
        foreach ($coupon_code as $c) {
            $check = CouponCodes::model()->where('id', $c)->first();
            if (!empty($check->order_used) || $check->is_active == 1) {
                continue;
            }
            CouponCodes::model()->where('id', $c)->update(['deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s', time())]);
        }
        return ["message" => "Đã xóa thành công!"];
    }
    public function updateCouponCode($id, Request $request)
    {
        $input = $request->all();
        // if (empty($input['code'])) {
        //     return $this->responseError(Message::get("V001", 'mã giảm giá'), 422);
        // }
        $coupon_code = $this->codeModel->find($id);

        if (!$coupon_code) {
            return $this->responseError(Message::get("V003", 'Mã giảm giá'), 400);
        }

        if (!empty($coupon_code->order_used) || $coupon_code->is_active == 1) {
            return $this->responseError(Message::get("V1002", 'Mã giảm giá'), 400);
        }

        if (!empty($input['code'])) {
            $coupon_code->code = $input['code'];
        }
        if (!empty($input['type'])) {
            $coupon_code->type = $input['type'];
        }
        if (!empty($input['discount'])) {
            $coupon_code->discount = $input['discount'];
        }
        if (!empty($input['limit_discount'])) {
            $coupon_code->limit_discount = $input['limit_discount'];
        }
        if (!empty($input['user_code'])) {
            $coupon_code->user_code = $input['user_code'];
        }
        if (!empty($input['start_date'])) {
            $coupon_code->start_date = date('Y-m-d', strtotime($input['start_date']));
        }
        if (!empty($input['end_date'])) {
            $coupon_code->end_date = date('Y-m-d', strtotime($input['end_date']));
        }

        $coupon_code->save();

        return ["data" => $coupon_code];
    }
}
