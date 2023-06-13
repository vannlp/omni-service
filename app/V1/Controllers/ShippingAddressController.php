<?php


namespace App\V1\Controllers;


use App\Cart;
use App\ShippingAddress;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\ShippingAddressModel;
use App\V1\Transformers\ShippingAddress\ShippingAddressTransformer;
use App\V1\Validators\ShippingAddress\ShippingAddressUpsertValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingAddressController extends BaseController
{
    /**
     * @var ShippingAddressModel
     */
    protected $model;

    /**
     * ShippingAddressController constructor.
     */
    public function __construct()
    {
        $this->model = new ShippingAddressModel();
    }

    /**
     * @param Request $request
     * @param ShippingAddressTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, ShippingAddressTransformer $transformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['full_name'])) {
            $input['full_name'] = ['like' => "%{$input['full_name']}%"];
        }
        if (!empty($input['phone'])) {
            $input['phone'] = ['like' => "%{$input['phone']}%"];
        }
        if (!empty($input['street_address'])) {
            $input['street_address'] = ['like' => "%{$input['street_address']}%"];
        }
        if (isset($input['is_default'])) {
            $input['is_default'] = ['=' => $input['is_default']];
        }
        if (empty($input['user_id'])) {
            $input['user_id'] = TM::getCurrentUserId();
        } else {
            $input['user_id'] = ['=' => "{$input['user_id']}"];
        }
        $input['sort']['is_default'] = "desc";
        $result                      = $this->model->search($input, [
            'getUser',
            'getUser.profile',
            'getCity:code,full_name',
            'getDistrict:code,full_name',
            'getWard:code,full_name',
            'createdBy.profile:id,user_id,full_name',
        ], $limit);
        return $this->response->paginator($result, $transformer);
    }

    /**
     * @param $id
     * @param ShippingAddressTransformer $transformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detail($id, ShippingAddressTransformer $transformer)
    {
        $result = ShippingAddress::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $transformer);
    }

    /**
     * @param Request $request
     * @param ShippingAddressUpsertValidator $validator
     * @param ShippingAddressTransformer $transformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function create(Request $request, ShippingAddressUpsertValidator $validator, ShippingAddressTransformer $transformer)
    {
        $input = $request->all();
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $transformer);
    }

    /**
     * @param $id
     * @param Request $request
     * @param ShippingAddressUpsertValidator $validator
     * @param ShippingAddressTransformer $transformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function update($id, Request $request, ShippingAddressUpsertValidator $validator, ShippingAddressTransformer $transformer)
    {
        $input       = $request->all();
        $userId      = TM::getCurrentUserId();
        $checkShippingAddress = ShippingAddress::model()->where(['id'=>$id,'user_id'=>$userId])->first();
        if(empty($checkShippingAddress)){
            return $this->responseError(Message::get("shipping_address.not-exist", "#{$id}"));
        }
        $input['id'] = $id;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($result, $transformer);
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        $userId      = TM::getCurrentUserId();
        $checkShippingAddress = ShippingAddress::model()->where(['id'=>$id,'user_id'=>$userId])->first();
        if(empty($checkShippingAddress)){
            return $this->responseError(Message::get("shipping_adress.not-exist", "#{$id}"));
        }
        try {
            DB::beginTransaction();
            $result = ShippingAddress::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $result->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("street_address.delete-success", $result->street_address)];
    }

    public function setIsDefault($id)
    {
        $result = ShippingAddress::find($id);

        if (empty($result)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }
        try {
            DB::beginTransaction();
            //Set Default = 0 All Shipping Address
            ShippingAddress::model()->where('user_id', TM::getCurrentUserId())->update(['is_default' => 0]);
            //Set Shipping Address Is 1
            $result->is_default = 1;
            $result->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("street_address.set-is-default", $result->street_address)];
    }

    public function setShippingAddressCart(Request $request)

    {
        $input = $request->all();

        $userId = TM::getCurrentUserId();
        if ($userId) {
            $cart = Cart::model()->where('user_id', $userId)->first();
        } else {
            if (empty($input['session_id'])) {
                return $this->responseError(Message::get("V001", 'session_id'));
            }
            $cart = Cart::model()->where('session_id', $input['session_id'])->first();
        }

        if (empty($cart)) {
            return $this->responseError(Message::get("V003", Message::get("carts")));
        }
        try {
            DB::beginTransaction();
            $cart->phone                  = $input['phone'] ?? null;
            $cart->full_name              = $input['full_name'] ?? null;
            $cart->description            = $input['note'] ?? null;
            $cart->customer_city_code     = $input['city_code'] ?? null;
            $cart->customer_district_code = $input['district_code'] ?? null;
            $cart->customer_ward_code     = $input['ward_code'] ?? null;
            $cart->street_address         = $input['street_address'] ?? null;
            $cart->customer_full_address  = $input['customer_full_address'] ?? null;
            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("street_address.set-street-address")];
    }
}