<?php


namespace App\V1\Controllers;

use App\Cart;
use App\City;
use App\CityHasRegion;
use App\Distributor;
use App\District;
use App\Order;
use App\V1\Library\GRAB;
use App\V1\Library\VTP;
use App\Ward;
use App\CartDetail;
use App\Category;
use App\CustomerInformation;
use App\Foundation\PromotionHandle;
use App\Product;
use App\ProductVariant;
use App\Session;
use App\ShippingMethod;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Actions\CartAddCouponAction;
use App\V1\Actions\CartRemoveCouponAction;
use App\V1\Actions\CartAddPromocodeAction;
use App\V1\Actions\CartRemovePromocodeAction;
use App\V1\Actions\CartRemoveCouponDeliveryAction;
use App\V1\Actions\CartAddVoucherAction;
use App\V1\Actions\CartRemoveVoucherAction;
use App\V1\Actions\ClientCartAddCouponAction;
use App\V1\Models\CartDetailModel;
use App\V1\Models\CartModel;
use App\V1\Transformers\Cart\CartTransformer;
use App\V1\Transformers\CustomerInformation\CustomerInformationTransformer;
use App\V1\Validators\CartUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\V1\Library\VNP;
use App\V1\Validators\Cart\UpdateCartAdminValidator;

/**
 * Class CartController
 * @package App\V1\Controllers
 */
class CartController extends BaseController
{
    /**
     * @var CartModel
     */
    protected $model;
    protected $cartDetailModel;

    /**
     * CartController constructor.
     */
    public function __construct()
    {
        $this->model           = new CartModel();
        $this->cartDetailModel = new CartDetailModel();
    }

    public function detail(Request $request)
    {
        try {
            $input      = $request->all();
            $sessionId  = $input['session_id'] ?? null;
            $userId     = TM::getCurrentUserId();
            $company_id = TM::getCurrentCompanyId();

            if ($userId) {
                app('request')->merge(['group_id' => TM::getCurrentGroupId()]);
                $this->mergeCartClientToUser($userId, $sessionId);
                $cart = Cart::with([
                    'details',
                    'details.product',
                    'details.file:id,code',
                    'details.getProduct:id,slug',
                    'details.getProduct.unit:id,name',
                    'getShippingAddress:id,phone,full_name',
                    'getShippingAddress.getWard',
                    'getShippingAddress.getDistrict',
                    'getShippingAddress.getCity',
                    'getShippingMethod:id,name',
                ])->where('user_id', $userId)->first();
                if (empty($cart)) {
                    return ['data' => null];
                }
                foreach ($cart->details as $key => $detail) {
                    $product_check = Product::model()->where('code', $detail->product_code)->where('status', 1)->first();
                    if (empty($product_check)) {
                        CartDetail::model()->where('product_code', $detail->product_code)->delete();
                    }
                }

                $cart = $this->model->checkWarehouse($cart);
                Log::view($this->model->getTable(), "#ID:" . $cart->id);
                // 
                //    $this->model->applyPromotion($cart);

                (new PromotionHandle())->promotionApplyCart($cart);
                //                        (new PromotionHandle())->promotionClientApplyCart($cart, $company_id);

            }

            if (!$userId && $sessionId) {
                list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
                app('request')->merge(['group_id' => $group_id]);
                $session_id = $request->input('session_id');
                $cart       = Cart::with([
                    'details',
                    'details.product',
                    'details.file:id,code',
                    'details.getProduct:id,slug',
                    'details.getProduct.unit:id,name',
                    'getShippingAddress:id,phone,full_name',
                    'getShippingAddress.getWard',
                    'getShippingAddress.getDistrict',
                    'getShippingAddress.getCity',
                    'getShippingMethod:id,name',
                ])->where('session_id', $session_id)->first();
                if (empty($cart)) {
                    return ['data' => null];
                }
                $cart = $this->model->checkWarehouse($cart);
                Log::view($this->model->getTable(), "#ID:" . $cart->id);
                (new PromotionHandle())->promotionClientApplyCart($cart, $company_id);

                //            $this->model->applyPromotion($cart);

            }
            if (!$userId && !$sessionId) {
                return $this->responseError(Message::get("V003", Message::get("carts")));
            }
            return $this->response->item($cart, new CartTransformer());
        } catch (\Exception $e) {
            $response = TM_Error::handle($e);
            return $this->responseError($response['message']);
        }
    }

    public function update(Request $request, CartUpdateValidator $cartUpdateValidator)
    {
        $input       = $request->all();
        $userId      = TM::getCurrentUserId();
        $cart        = Cart::model()->where('user_id', $userId)->first();
        $id          = $cart->id ?? 0;
        $input['id'] = $id;
        $cart        = $cartUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            if (empty($cart)) {
                return $this->responseError(Message::get("carts.not-exist", "#{$input['id']}"));
            }
            $cart = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $cart->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.update-success")];
    }

    private function mergeCartClientToUser($userId, $sessionId)
    {
        $cartClient = Cart::with('details')->where('session_id', $sessionId)->whereNull('user_id')->first();

        $cartUser = Cart::with('details')->where('user_id', $userId)->first();
        try {
            DB::beginTransaction();
            if (!empty($cartClient) && !empty($cartUser)) {
                foreach ($cartClient->details as $cl) {
                    $checkCartUser = CartDetail::model()
                        ->where('cart_id', $cartUser->id)
                        ->where('product_id', $cl->product_id)
                        ->first();

                    if (!empty($checkCartUser)) {
                        $checkCartUser->quantity += $cl->quantity;
                        $checkCartUser->save();
                        CartDetail::where('id', $cl->id)->delete();
                    } else {
                        CartDetail::where('id', $cl->id)
                            ->update([
                                'cart_id'         => $cartUser->id,
                                'promotion_price' => 0,
                                'total'           => $cartUser->quantity * $cartUser->price,
                            ]);
                    }
                }
                $cartClient->delete();
            }

            if (!empty($cartClient) && empty($cartUser)) {
                $cart          = Cart::where('session_id', $sessionId)->first();
                $cart->user_id = $userId;
                $cart->save();

                CartDetail::where('cart_id', $cart->id)
                    ->update([
                        'promotion_price' => 0
                    ]);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($ex->getMessage());
        }
        return true;
    }

    public function addToCart(Request $request)
    {
        $input       = $request->all();
        $session_id  = $input['session_id'] ?? null;
        $userCurrent = TM::getCurrentUserId();
        if (empty($session_id)) {
            return $this->responseError(Message::get("V009", Message::get('session_id')));
        }
        $checkSession = Session::model()->where('session_id', "{$session_id}")->select('id')->first();
        if (empty($checkSession)) {
            return $this->responseError(Message::get("V003", Message::get('session_id')));
        }

        if ($userCurrent && $session_id) {
            $this->mergeCartClientToUser($userCurrent, $session_id);
            $result = $this->userAddToCart($request, $session_id);
        }

        if (!$userCurrent && $session_id) {
            $result = $this->clientAddToCart($request);
        }

        return $result;
    }

    public function userAddToCart(Request $request, $session_id)
    {
        $input = $request->all();
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $active_status = DB::table('active_status')->where('name', 'ORDER_APP')->where('is_active', 1)->first();
        // Tạm chặn không cho đặt hàng từ APP
        if (get_device() == 'APP' && empty($active_status)) {
           return $this->responseError('Hiện tại app Nutifood tạm ngưng phát triển, quý khách vui lòng truy cập qua website Nutifoodshop.com để đặt hàng');
        }

        if (empty($input['product_id'])) {
            return response()->json([
                'message' => Response::HTTP_UNPROCESSABLE_ENTITY . " " . Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                'errors'  => ['product_id' => [Message::get("V001", Message::get("product_id"))]],
            ], 422);
        }
        try {
            DB::beginTransaction();
            $userId = TM::getCurrentUserId();

            $quantity = array_get($input, 'quantity', 1);

            $product = Product::with('warehouse')
                ->where('id', $input['product_id'])
                ->whereHas('stores', function ($query) {
                    $query->where('store_id', TM::getCurrentStoreId());
                });

            $category_ids = (new Category())->getIdsOfProduct($store_id, $area_ids);

            $product = $product->where(function ($q) use ($category_ids) {
                foreach ($category_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            });

            $product = $product->with('priceDetail')->first();

            if (!empty($input['qr_scan'])) {
                $product->qr_scan += 1;
                $product->save();
            }

            if (empty($product)) {
                return $this->responseError(Message::get("V003", Message::get('product_id')));
            }

            $inventory = Arr::get($product->warehouse, 'quantity', 0);

            if ($inventory <= 0) {
                return $this->responseError(Message::get("V017"));
            }

            if ($inventory < $quantity) {
                return $this->responseError(Message::get("V013", Message::get("quantity"), $inventory));
            }

            $price = Arr::get($product->priceDetail($product), 'price', $product->price);

            $total = $price * $quantity;

            $checkProductVarient = ProductVariant::model()->where('product_id', $product->id)->select('id')->first();
            if (!empty($checkProductVarient)) {
                if (empty($input['options'])) {
                    return $this->responseError(Message::get("V009", Message::get('product_variant_id')));
                }
            } else {
                if (!empty($input['options'])) {
                    return $this->responseError(Message::get("V003", Message::get('product_variant_id')));
                }
            }
            $cart = Cart::where('user_id', $userId)->select('id', 'qr_scan')->first();
            if (empty($cart)) {
                $param = [
                    'user_id'        => $userId,
                    'store_id'       => $store_id,
                    'company_id'     => $company_id,
                    'address'        => null,
                    'description'    => null,
                    'phone'          => null,
                    'payment_method' => PAYMENT_METHOD_CASH,
                    'receiving_time' => null
                ];
                $cart  = $this->model->create($param);

                $cartDetail = $this->cartDetailModel->create([
                    'cart_id'             => $cart->id,
                    'product_id'          => $product->id,
                    'product_code'        => $product->code,
                    'product_name'        => $product->name,
                    'product_category'    => $product->category_ids,
                    'product_description' => $product->description,
                    'options'             => $input['options'] ?? null,
                    'price'               => $price,
                    'old_product_price'   => $product->price,
                    'product_thumb'       => $product->thumbnail,
                    'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                    'length'              => $product->length,
                    'width'               => $product->width,
                    'quantity'            => $quantity,
                    'total'               => $total
                ]);
            } else {
                $cartDetail = CartDetail::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->select('id')->get()->toArray();
                if (empty($cartDetail)) {
                    $paramDetail = [
                        'cart_id'             => $cart->id,
                        'product_id'          => $product->id,
                        'product_code'        => $product->code,
                        'product_name'        => $product->name,
                        'product_description' => $product->description,
                        'product_category'    => $product->category_ids,
                        'options'             => $input['options'] ?? null,
                        'price'               => $price,
                        'old_product_price'   => $product->price,
                        'product_thumb'       => $product->thumbnail,
                        'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                        'length'              => $product->length,
                        'width'               => $product->width,
                        'quantity'            => $quantity,
                        'total'               => $total,
                    ];
                    $cartDetail  = $this->cartDetailModel->create($paramDetail);
                } else {
                    if (empty($input['options'])) {
                        $cartDetail = CartDetail::model()
                            ->where('cart_id', $cart->id)
                            ->where('product_id', $product->id)
                            ->where(function ($q) {
                                $q->whereNull('options');
                                $q->orWhere('options', '[]');
                                $q->orWhere('options', '');
                            })->select('price','quantity','total','id')->first();
                        if (empty($cartDetail)) {
                            $paramDetail = [
                                'cart_id'             => $cart->id,
                                'product_id'          => $product->id,
                                'product_code'        => $product->code,
                                'product_name'        => $product->name,
                                'product_description' => $product->description,
                                'product_category'    => $product->category_ids,
                                'options'             => $input['options'] ?? null,
                                'price'               => $price,
                                'old_product_price'   => $product->price,
                                'product_thumb'       => $product->thumbnail,
                                'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                                'length'              => $product->length,
                                'width'               => $product->width,
                                'quantity'            => $quantity,
                                'total'               => $total,
                            ];
                            $cartDetail  = $this->cartDetailModel->create($paramDetail);
                        } else {
                            $cartDetail->price    = $price;
                            $cartDetail->quantity += $quantity;
                            $cartDetail->total    += $total;
                            $cartDetail->save();
                        }
                    } else {
                        $flag = false;
                        $key  = 0;
                        foreach ($cartDetail as $count => $item) {
                            $inputOption        = $input['options'];
                            $optionInCartDetail = $item['options'];
                            sort($inputOption);
                            sort($optionInCartDetail);
                            $inputOption        = implode("", $inputOption);
                            $optionInCartDetail = implode("", $optionInCartDetail);
                            if ($inputOption === $optionInCartDetail) {
                                $flag = true;
                                $key  = $count;
                                break;
                            }
                        }
                        if ($flag) {
                            $cartDetail           = CartDetail::where('id', $cartDetail[$key]['id'])->select('price', 'quantity', 'total', 'id')->first();
                            $cartDetail->price    = $price;
                            $cartDetail->quantity += $quantity;
                            $cartDetail->total    += $total;
                            $cartDetail->save();
                        } else {
                            $paramDetail = [
                                'cart_id'             => $cart->id,
                                'product_id'          => $product->id,
                                'product_code'        => $product->code,
                                'product_name'        => $product->name,
                                'product_description' => $product->description,
                                'product_category'    => $product->category_ids,
                                'options'             => $input['options'] ?? null,
                                'price'               => $price,
                                'old_product_price'   => $product->price,
                                'product_thumb'       => $product->thumbnail,
                                'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                                'length'              => $product->length,
                                'width'               => $product->width,
                                'quantity'            => $quantity,
                                'total'               => $total,
                            ];
                            $cartDetail  = $this->cartDetailModel->create($paramDetail);
                        }
                    }
                }
            }
            if (!empty($input['qr_scan'])) {
                $cart->qr_scan = 1;
            }
            $cart->save();

            DB::commit();
            return [
                'status' => Message::get("carts.create-success"),
                'data'   => [
                    'id' => $cartDetail->id,
                ]
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($ex->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();
            $input      = $request->all();
            $userId     = TM::getCurrentUserId();
            $session_id = $input['session_id'] ?? null;
            if ($userId) {
                $cart = Cart::model()->where('user_id', $userId)->first();
                if (empty($cart)) {
                    return $this->responseError(Message::get("V003", Message::get('carts')));
                }
                // 1. Delete Cart Detail
                CartDetail::model()->where('cart_id', $cart->id)->delete();
                // 2. Delete Cart
                $cart->delete();
            } else {
                $cart = Cart::model()->where('session_id', $session_id)->first();
                if (empty($cart)) {
                    return $this->responseError(Message::get("V003", Message::get('carts')));
                }
                // 1. Delete Cart Detail
                CartDetail::model()->where('cart_id', $cart->id)->delete();
                // 2. Delete Cart
                $cart->delete();
            }

            Log::delete($this->model->getTable(), "#ID:" . $cart->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.delete-success")];
    }

    public function removeProductInCart($id)
    {
        try {
            DB::beginTransaction();
            $cartDetail = CartDetail::find($id);
            if (empty($cartDetail)) {
                return $this->responseError(Message::get("V003", "ID #$id"));
            }
            // 2. Delete Cart
            $cartDetail->delete();
            Log::delete($this->model->getTable(), "#ID:" . $cartDetail->id);
            //Check Cart Of User
            $cartId     = $cartDetail->cart_id;
            $cartDetail = CartDetail::model()->where('cart_id', $cartId)->first();

            if (empty($cartDetail)) {
                $cart = Cart::find($cartId);
                $cart->delete();
            }
            //            $totals           = $this->model->applyPromotion($cart);
            //            $cart->total_info = $totals;
            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.remove-item-success"),
            //            'data'   => !empty($totals) ? ['totals' => $totals ?? null] : null,
        ];
    }

    public function updateProductInCart($id, Request $request)
    {
        $input = $request->all();
        if (!isset($input['quantity'])) {
            return response()->json([
                'message' => Response::HTTP_UNPROCESSABLE_ENTITY . " " . Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                'errors'  => ['quantity' => [Message::get("V001", Message::get("quantity"))]],
            ], 422);
        }

        $cartDetail = CartDetail::find($id);
        if (empty($cartDetail)) {
            return $this->responseError(Message::get("V003", "ID #$id"));
        }
        if ($input['quantity'] < 0) {
            return $this->responseError(Message::get('V010', Message::get('quantity'), 0));
        }

        $product = Product::with('warehouse')->find($cartDetail->product_id);

        $inventory = Arr::get($product->warehouse, 'quantity', 0);

        if ($inventory <= 0) {
            return $this->responseError(Message::get("V017"));
        }

        if ($inventory < $input['quantity'] ?? 0) {
            return $this->responseError(Message::get("V013", Message::get("quantity"), $inventory));
        }
        try {
            DB::beginTransaction();
            $quantity             = $input['quantity'];
            $productPrice         = $cartDetail->price;
            $total                = $quantity * $productPrice;
            $cartDetail->quantity = $quantity;
            $cartDetail->total    = $total;
            $cart                 = Cart::find($cartDetail->cart_id);
            if ($quantity == 0) {
                $cartDetail->delete();
            }
            $cartDetail->save();

            //            $totals           = $this->model->applyPromotion($cart);
            //            $cart->total_info = $totals;
            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.update-success")
            //                , 'data' => ['totals' => $totals ?? null]
        ];
    }

    public function updateNoteProductInCart($id, Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $cartDetail = CartDetail::find($id);
            if (empty($cartDetail)) {
                return $this->responseError(Message::get("V003", "ID #$id"));
            }
            $note             = !empty($input['note']) ? $input['note'] : null;
            $cartDetail->note = $note;
            $cartDetail->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.update-success")];
    }

    public function removeCouponDelivery(Request $request, CartRemoveCouponDeliveryAction $action)
    {
        $action->handle();

        return ['status' => Message::get("carts.update-success")];
    }


    public function addCoupon(Request $request, CartAddCouponAction $action)
    {
        if (!empty($request->input('coupon_code')) && !empty($request->input('coupon_delivery_code'))) {
            $rules = [
                'coupon_code'          => 'required',
                'coupon_delivery_code' => 'required',
            ];
        } elseif (!empty($request->input('coupon_code'))) {
            $rules = [
                'coupon_code' => 'required',
            ];
        } else {
            $rules = [
                'coupon_delivery_code' => 'required',
            ];
        }


        $this->validate($request, $rules);

        $action->handle();

        if (!$action->isSuccess()) {
            return $this->responseError($action->errors()[0]);
        }

        return ['status' => Message::get("carts.update-success")];
    }

    public function removeCoupon(Request $request, CartRemoveCouponAction $action)
    {
        $action->handle();

        return ['status' => Message::get("carts.update-success")];
    }


    // public function addPromocode(Request $request, CartAddPromocodeAction $action)
    // {
    //     $rules = [
    //         'promocode_code' => 'required',
    //     ];

    //     $this->validate($request, $rules);

    //     $action->handle();

    //     if (!$action->isSuccess()) {
    //         return $this->responseError($action->errors()[0]);
    //     }

    //     return ['status' => Message::get("carts.update-success")];
    // }

    // public function removePromocode(Request $request, CartRemovePromocodeAction $action)
    // {
    //     $action->handle();

    //     return ['status' => Message::get("carts.update-success")];
    // }

    // public function addVoucher(Request $request, CartAddVoucherAction $action)
    // {
    //     $rules = [
    //         'voucher_code' => 'required',
    //     ];

    //     $this->validate($request, $rules);

    //     $action->handle();

    //     if (!$action->isSuccess()) {
    //         return $this->responseError($action->errors()[0]);
    //     }

    //     return ['status' => Message::get("carts.update-success")];
    // }

    public function removeVoucher(Request $request, CartRemoveVoucherAction $action)
    {
        $action->handle();

        return ['status' => Message::get("carts.update-success")];
    }

    public function clientSetPaymentMethod(Request $request)
    {
        $input = $request->all();

        if (empty($input['payment_method'])) {
            throw new \Exception(Message::get("V001", 'payment_method'));
        }
        $userId = TM::getCurrentUserId();
        if ($userId) {
            $cart = Cart::model()->where('user_id', $userId)->first();
        } else {
            if (empty($input['session_id'])) {
                throw new \Exception(Message::get("V001", 'session_id'));
            }
            $cart = Cart::model()->where('session_id', $input['session_id'])->first();
        }

        if (empty($cart)) {
            throw new \Exception(Message::get("V003", Message::get("carts")));
        }
        if (!in_array($input['payment_method'], array_keys(PAYMENT_METHOD_NAME))) {
            throw new \Exception(Message::get("V001", 'payment_method'));
        }
        try {
            DB::beginTransaction();
            $cart->payment_method = $input['payment_method'];
            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.set-payment-method-success")];
    }

    public function setShippingMethod(Request $request)
    {
        $input  = $request->all();
        $userId = TM::getCurrentUserId();
        if ($userId) {
            $cart = Cart::model()->select('id')->where('user_id', $userId)->first();
        } else {
            if (empty($input['session_id'])) {
                return $this->responseError(Message::get("V001", Message::get("session_id")));
            }
            $cart = Cart::model()->select('id')->where('session_id', $input['session_id'])->first();
        }

        if (empty($cart)) {
            return $this->responseError(Message::get("V003", Message::get("carts")));
        }
        //        $key = array_search('total', array_column($cart->total_info, 'code'));
        try {
            DB::beginTransaction();
            $cart->shipping_method        = $input['shipping_method'] ?? null;
            $cart->shipping_method_code   = $input['shipping_code'] ?? null;
            $cart->shipping_method_name   = $input['shipping_name'] ?? null;
            $cart->shipping_service       = $input['shipping_service'] ?? null;
            $cart->shipping_note          = $input['shipping_note'] ?? null;
            $cart->shipping_diff          = $input['shipping_diff'] ?? null;
            $cart->service_name           = $input['service_name'] ?? null;
            $cart->extra_service          = $input['extra_service'] ?? null;
            $cart->ship_fee               = $input['ship_fee'] ?? null;
            $cart->estimated_deliver_time = $input['estimated_deliver_time'] ?? null;
            $cart->lading_method          = $input['lading_method'] ?? null;
            $cart->ship_fee_start         = $input['ship_fee_start'] ?? 0;
            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.set-shipping-method-success")];
    }

    // Client
    public function clientAddToCart(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $active_status = DB::table('active_status')->where('name', 'ORDER_APP')->where('is_active', 1)->first();
        // Tạm chặn không cho đặt hàng từ APP
        if (get_device() == 'APP' && empty($active_status)) {
            if (get_device() == 'APP' && empty($active_status)) {
                return $this->responseError('Hiện tại app Nutifood tạm ngưng phát triển, quý khách vui lòng truy cập qua website Nutifoodshop.com để đặt hàng');
             }
        }

        $input      = $request->all();
        $session_id = $request->input('session_id');
        if (empty($input['product_id'])) {
            return response()->json([
                'message' => Response::HTTP_UNPROCESSABLE_ENTITY . " " . Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                'errors'  => ['product_id' => [Message::get("V001", Message::get("product_id"))]],
            ], 422);
        }
        try {
            DB::beginTransaction();
            $quantity = array_get($input, 'quantity', 1);
            $product  = Product::with('warehouse')
                ->where('id', $input['product_id'])
                ->whereHas('stores', function ($query) use ($store_id) {
                    $query->where('store_id', $store_id);
                });

            $category_ids = (new Category())->getClientIdsOfProduct($store_id);

            $product = $product->where(function ($q) use ($category_ids) {
                foreach ($category_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            });
            $product = $product->with('priceDetail')->first();
            if (empty($product)) {
                return $this->responseError(Message::get("V003", Message::get('product_id')));
            }

            if (!empty($input['qr_scan'])) {
                $product->qr_scan += 1;
                $product->save();
            }

            $inventory = Arr::get($product->warehouse, 'quantity', 0);

            if ($inventory <= 0) {
                return $this->responseError(Message::get("V017"));
            }
            if ($inventory < $quantity) {
                return $this->responseError(Message::get("V013", Message::get("quantity"), $inventory));
            }
            $price               = Arr::get($product->priceDetail($product), 'price', $product->price);
            $total               = $price * $quantity;
            $checkProductVarient = ProductVariant::model()->where('product_id', $product->id)->first();
            if (!empty($checkProductVarient)) {
                if (empty($input['options'])) {
                    return $this->responseError(Message::get("V009", Message::get('product_variant_id')));
                }
            } else {
                if (!empty($input['options'])) {
                    return $this->responseError(Message::get("V003", Message::get('product_variant_id')));
                }
            }
            if (empty($session_id)) {
                return $this->responseError(Message::get("V009", Message::get('session_id')));
            }
            $checkSession = Session::model()->where('session_id', "{$session_id}")->select('session_id')->first();
            if (empty($checkSession)) {
                return $this->responseError(Message::get("V003", Message::get('session_id')));
            }
            $cart = Cart::model()->where('session_id', $session_id)->select('id','qr_scan')->first();

            if (empty($cart)) {
                $param      = [
                    'session_id'     => $session_id,
                    'store_id'       => $store_id,
                    'company_id'     => $company_id,
                    'address'        => null,
                    'description'    => null,
                    'phone'          => null,
                    'payment_method' => PAYMENT_METHOD_CASH,
                    'receiving_time' => null
                ];
                $cart       = $this->model->create($param);
                $cartDetail = $this->cartDetailModel->create([
                    'cart_id'             => $cart->id,
                    'product_id'          => $product->id,
                    'product_code'        => $product->code,
                    'product_name'        => $product->name,
                    'product_description' => $product->description,
                    'product_category'    => $product->category_ids,
                    'options'             => $input['options'] ?? null,
                    'price'               => $price,
                    'old_product_price'   => $product->price,
                    'product_thumb'       => $product->thumbnail,
                    'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                    'length'              => $product->length,
                    'width'               => $product->width,
                    'quantity'            => $quantity,
                    'total'               => $total
                ]);
            } else {
                $cartDetail = CartDetail::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->get()->toArray();

                if (empty($cartDetail)) {
                    $paramDetail = [
                        'cart_id'             => $cart->id,
                        'product_id'          => $product->id,
                        'product_code'        => $product->code,
                        'product_name'        => $product->name,
                        'product_description' => $product->description,
                        'product_category'    => $product->category_ids,
                        'options'             => $input['options'] ?? null,
                        'price'               => $price,
                        'old_product_price'   => $product->price,
                        'product_thumb'       => $product->thumbnail,
                        'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                        'length'              => $product->length,
                        'width'               => $product->width,
                        'quantity'            => $quantity,
                        'total'               => $total,
                    ];
                    $cartDetail  = $this->cartDetailModel->create($paramDetail);
                } else {
                    if (empty($input['options'])) {
                        $cartDetail = CartDetail::model()
                            ->where('cart_id', $cart->id)
                            ->where('product_id', $product->id)
                            ->where(function ($q) {
                                $q->whereNull('options');
                                $q->orWhere('options', '[]');
                                $q->orWhere('options', '');
                            })->select('price', 'quantity', 'total', 'id')->first();
                        if (empty($cartDetail)) {
                            $paramDetail = [
                                'cart_id'             => $cart->id,
                                'product_id'          => $product->id,
                                'product_code'        => $product->code,
                                'product_name'        => $product->name,
                                'product_description' => $product->description,
                                'product_category'    => $product->category_ids,
                                'options'             => $input['options'] ?? null,
                                'price'               => $price,
                                'old_product_price'   => $product->price,
                                'product_thumb'       => $product->thumbnail,
                                'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                                'length'              => $product->length,
                                'width'               => $product->width,
                                'quantity'            => $quantity,
                                'total'               => $total,
                            ];
                            $cartDetail  = $this->cartDetailModel->create($paramDetail);
                        } else {

                            $cartDetail->price    = $price;
                            $cartDetail->quantity += $quantity;
                            $cartDetail->total    += $total;
                            $cartDetail->save();
                        }
                    } else {
                        $flag = false;
                        $key  = 0;
                        foreach ($cartDetail as $count => $item) {
                            $inputOption        = $input['options'];
                            $optionInCartDetail = $item['options'];
                            sort($inputOption);
                            sort($optionInCartDetail);
                            $inputOption        = implode("", $inputOption);
                            $optionInCartDetail = implode("", $optionInCartDetail);
                            if ($inputOption === $optionInCartDetail) {
                                $flag = true;
                                $key  = $count;
                                break;
                            }
                        }
                        if ($flag) {
                            $cartDetail           = CartDetail::where('id', $cartDetail[$key]['id'])->select('price', 'quantity', 'total', 'id')->first();
                            $cartDetail->price    = $price;
                            $cartDetail->quantity += $quantity;
                            $cartDetail->total    += $total;
                            $cartDetail->save();
                        } else {
                            $paramDetail = [
                                'cart_id'             => $cart->id,
                                'product_id'          => $product->id,
                                'product_code'        => $product->code,
                                'product_name'        => $product->name,
                                'product_description' => $product->description,
                                'product_category'    => $product->category_ids,
                                'options'             => $input['options'] ?? null,
                                'price'               => $price,
                                'old_product_price'   => $product->price,
                                'product_thumb'       => $product->thumbnail,
                                'weight'              => $product->weight_class == 'KG' ? $product->weight * 1000 : $product->weight,
                                'length'              => $product->length,
                                'width'               => $product->width,
                                'quantity'            => $quantity,
                                'total'               => $total,
                            ];
                            $cartDetail  = $this->cartDetailModel->create($paramDetail);
                        }
                    }
                }
            }
            if (!empty($input['qr_scan'])) {
                $cart->qr_scan = 1;
                $cart->save();
            }
            //            (new PromotionHandle())->promotionClientApplyCart($cart, $company_id);
            DB::commit();
            return [
                'status' => Message::get("carts.create-success"),
                'data'   => [
                    'id' => $cartDetail->id,
                    //                    'totals' => $cart->total_info,
                ]
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message'], 400);
        }
    }

    public function clientDelete($session, Request $request)
    {
        try {
            $input = $request->all();
            $userId = TM::getCurrentUserId();
            if ($userId) {
                $cart = Cart::model()->select('id')->where('user_id', $userId)->first();
            } else {
                if (empty($session)) {
                    $this->responseError(Message::get("V001", Message::get("session_id")));
                }
                $cart = Cart::model()->where('session_id', $session)->first();
            }
            if (empty($cart)) {
                $this->responseError(Message::get("V003", Message::get("carts")));
            }
            // 1. Delete Cart Detail
            CartDetail::model()->where('cart_id', $cart->id)->delete();
            // 2. Delete Cart
            $cart->delete();
            Log::delete($this->model->getTable(), "#ID:" . $cart->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.delete-success")];
    }

    public function updateClientProductInCart($id, Request $request)
    {
        $input = $request->all();
        if (!isset($input['quantity'])) {
            return response()->json([
                'message' => Response::HTTP_UNPROCESSABLE_ENTITY . " " . Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                'errors'  => ['quantity' => [Message::get("V001", Message::get("quantity"))]],
            ], 422);
        }

        $cartDetail = CartDetail::find($id);

        if (empty($cartDetail)) {
            return $this->responseError(Message::get("V003", "ID #$id"));
        }
        if ($input['quantity'] < 0) {
            return $this->responseError(Message::get('V010', Message::get('quantity'), 0));
        }
        $product          = Product::with('warehouse')->find($cartDetail->product_id);
        $productWarehouse = Arr::get($product, 'warehouse', null);
        if (empty($productWarehouse)) {
            return $this->responseError(Message::get("V060"));
        }
        $inventory = Arr::get($product->warehouse, 'quantity', 0);

        if (!empty($input['qr_scan'])) {
            $product->qr_scan += 1;
            $product->save();
        }

        if ($inventory <= 0) {
            return $this->responseError(Message::get("V017"));
        }

        if ($inventory < $input['quantity'] ?? 0) {
            return $this->responseError(Message::get("V013", Message::get("quantity"), $inventory));
        }
        try {
            DB::beginTransaction();
            $quantity             = $input['quantity'];
            $productPrice         = $cartDetail->price;
            $total                = $quantity * $productPrice;
            $cartDetail->quantity = $quantity;
            $cartDetail->total    = $total;
            if ($quantity == 0) {
                $cartDetail->delete();
            }
            $cartDetail->save();
            //            $cart             = Cart::find($cartDetail->cart_id);
            //            $totals           = $this->model->applyPromotion($cart);
            //            $cart->total_info = $totals;
            //            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.update-success")
            //                , 'data' => ['totals' => $totals ?? null]
        ];
    }

    public function updateClientNoteProductInCart($id, Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $cartDetail = CartDetail::find($id);
            if (empty($cartDetail)) {
                return $this->responseError(Message::get("V003", "ID #$id"));
            }
            $note             = !empty($input['note']) ? $input['note'] : null;
            $cartDetail->note = $note;
            $cartDetail->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.update-success")];
    }

    public function removeClientProductInCart($id)
    {
        try {
            DB::beginTransaction();
            $cartDetail = CartDetail::find($id);
            if (empty($cartDetail)) {
                return $this->responseError(Message::get("V003", "ID #$id"));
            }
            // 2. Delete Cart
            $cartDetail->delete();
            Log::delete($this->model->getTable(), "#ID:" . $cartDetail->id);
            //Check Cart Of User
            $cartId     = $cartDetail->cart_id;
            $cartDetail = CartDetail::model()->select('id')->where('cart_id', $cartId)->first();
            if (empty($cartDetail)) {
                $cart = Cart::find($cartId);
                $cart->delete();
            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.remove-item-success"),
            'data'   => !empty($totals) ? ['totals' => $totals ?? null] : null,
        ];
    }

    public function removeAdminProductInCart($id)
    {
        try {
            DB::beginTransaction();
            $cartDetail = CartDetail::find($id);
            if (empty($cartDetail)) {
                return $this->responseError(Message::get("V003", "ID #$id"));
            }
            // 2. Delete Cart
            $cartDetail->delete();
            Log::delete($this->model->getTable(), "#ID:" . $cartDetail->id);
            //Check Cart Of User
            // $cartId     = $cartDetail->cart_id;
            // $cartDetail = CartDetail::model()->select('id')->where('cart_id', $cartId)->first();
            // if (empty($cartDetail)) {
            //     $cart = Cart::find($cartId);
            //     $cart->delete();
            // }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.remove-item-success"),
            'data'   => !empty($totals) ? ['totals' => $totals ?? null] : null,
        ];
    }

    public function removeClientProductInCartIds(Request $request)
    {
        $input      = $request->all();
        $userId     = TM::getCurrentUserId();
        $session_id = $input['session_id'] ?? null;
        $ids        = explode(",", $input['cart_detail']);
        try {
            DB::beginTransaction();
            if ($userId) {
                $cart = Cart::model()->where('user_id', $userId)->first();
                if (empty($cart)) {
                    return $this->responseError(Message::get("V003", Message::get('carts')));
                }
                $cartDetail = CartDetail::model()->whereIn('id', $ids)->where('cart_id', $cart->id)->get();
                foreach ($cartDetail as $key) {
                    $key->delete();
                    Log::delete($this->model->getTable(), "#ID:" . $key->id);
                }
            } else {
                $cart = Cart::model()->where('session_id', $session_id)->first();
                if (empty($cart)) {
                    return $this->responseError(Message::get("V003", Message::get('carts')));
                }
                // 1. Delete Cart Detail
                $cartDetail = CartDetail::model()->whereIn('id', $ids)->where('cart_id', $cart->id)->get();
                foreach ($cartDetail as $key) {
                    $key->delete();
                    Log::delete($this->model->getTable(), "#ID:" . $key->id);
                }
            }
            //Check Cart Of User
            $cartId     = $cart->id;
            $cartDetail = CartDetail::where('cart_id', $cartId)->first();
            if (empty($cartDetail)) {
                $cart = Cart::find($cartId);
                $cart->delete();
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.remove-item-success"),
            'data'   => !empty($totals) ? ['totals' => $totals ?? null] : null,
        ];
    }

    public function getCustomerInfo($phone, Request $request)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        $cusInfo = CustomerInformation::where([
            'phone'    => $phone,
            'store_id' => $store_id
        ])->first();
        return $this->response->item($cusInfo, new CustomerInformationTransformer());
    }

    public function clientAddCoupon(Request $request, ClientCartAddCouponAction $action)
    {
        $input      = $request->all();
        $session_id = $input['session_id'] ?? null;
        if (empty($session_id)) {
            return $this->responseError(Message::get("V009", Message::get('session_id')));
        }
        $checkSession = Session::model()->where('session_id', $session_id)->select('id')->first();
        if (empty($checkSession)) {
            return $this->responseError(Message::get("V003", Message::get('session_id')));
        }

        if (empty($input['coupon_code'])) {
            return $this->responseError(Message::get("V001", Message::get('coupon_code')));
        }

        $action->handle();

        if (!$action->isSuccess()) {
            return $this->responseError(Message::get("coupon.update-error"), 442);
        }

        return ['status' => Message::get("coupon.update-success")];
    }

    public function adminAddCoupon(Request $request, ClientCartAddCouponAction $action)
    {
        $input      = $request->all();
        $session_id = $input['session_id'] ?? null;
        if (empty($session_id)) {
            return $this->responseError(Message::get("V009", Message::get('session_id')));
        }
        $checkSession = Session::model()->where('session_id', $session_id)->select('id')->first();
        if (empty($checkSession)) {
            return $this->responseError(Message::get("V003", Message::get('session_id')));
        }

        if (empty($input['coupon_code'])) {
            return $this->responseError(Message::get("V001", Message::get('coupon_code')));
        }

        $action->handle();

        if (!$action->isSuccess()) {
            return $this->responseError(Message::get("coupon.update-error"), 442);
        }

        return ['status' => Message::get("coupon.update-success")];
    }

    public function clientUpdateCartInfo(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        $userId = TM::getCurrentUserId();
        if ($userId) {
            $cart = Cart::model()->with(
                'details',
                'details.getProduct',
                'details.file',
                'createdBy',
                'createdBy.profile',
                'getCity',
                'getDistrict',
                'getWard',
            )->where('user_id', $userId)->first();
        } else {
            if (empty($input['session_id'])) {
                return $this->responseError(Message::get("V001", Message::get("session_id")));
            }
            $cart = Cart::model()->with('details', 'details.product')->where('session_id', $input['session_id'])->first();
        }
        if (empty($cart)) {
            return $this->responseError(Message::get("V003", Message::get("carts")));
        }
        //        $cart  = Cart::model()->with('details', 'details.product')->where('id', $id)->first();
        //        if (empty($cart)) {
        //            return $this->responseError(Message::get("V003", "ID #$id"));
        //        }
        // if ($input['city_code']) {
        //     $city_code_before = $input['city_code'];
        //     $city_code_before_name = City::where('code', $city_code_before)->first();
        //     foreach ($cart->details as $detail) {
        //         if (!$saleArea = $detail->product->sale_area) {
        //             continue;
        //         }
        //         $saleArea = json_decode($saleArea, true);
        //         $key      = array_search($city_code_before, array_column($saleArea, 'code'));
        //         if (!is_numeric($key)) {
        //             return $this->responseError("Sản phẩm [$detail->product_name] không được giao ở [$city_code_before_name->full_name]. Quý khách vui lòng gỡ bỏ sản phẩm này khỏi đơn hàng trước khi thanh toán. Mong quý khách thông cảm!");
        //         }
        //     }
        // }
        // if ($input['district_code'] && $input['city_code']) {
        //     $district_code_before = $input['district_code'];
        //     $district_code_before_name = District::where('code', $district_code_before)->first();
        //     foreach ($cart->details as $detail) {
        //         if (!$saleArea = $detail->product->sale_area) {
        //             continue;
        //         }
        //         $saleArea = json_decode($saleArea, true);
        //         $city_key = array_search($input['city_code'], array_column($saleArea, 'code'));
        //         if(!empty($saleArea[$city_key]['districts'])){
        //             $key      = array_search($district_code_before, array_column($saleArea[$city_key]['districts'], 'code'));
        //             if (!is_numeric($key)) {
        //                 return $this->responseError("Sản phẩm [$detail->product_name] không được giao ở [$district_code_before_name->full_name]. Quý khách vui lòng gỡ bỏ sản phẩm này khỏi đơn hàng trước khi thanh toán. Mong quý khách thông cảm!");
        //             }
        //         }
        //     }
        // }
        // if ($input['district_code'] && $input['city_code'] && $input['ward_code']) {
        //     $ward_code_before = $input['ward_code'];
        //     $ward_code_before_name = Ward::where('code', $ward_code_before)->first();
        //     foreach ($cart->details as $detail) {
        //         if (!$saleArea = $detail->product->sale_area) {
        //             continue;
        //         }
        //         $saleArea = json_decode($saleArea, true);
        //         $city_key = array_search($input['city_code'], array_column($saleArea, 'code'));
        //         if(!empty($saleArea[$city_key]['districts'])){
        //             $district_key = array_search($input['district_code'], array_column($saleArea[$city_key]['districts'], 'code'));
        //             if(!empty($saleArea[$city_key]['districts'][$district_key]['wards'])){
        //                 $key      = array_search($ward_code_before, array_column($saleArea[$city_key]['districts'][$district_key]['wards'], 'code'));
        //                 if (!is_numeric($key)) {
        //                     return $this->responseError("Sản phẩm [$detail->product_name] không được giao ở [$ward_code_before_name->full_name]. Quý khách vui lòng gỡ bỏ sản phẩm này khỏi đơn hàng trước khi thanh toán. Mong quý khách thông cảm!");
        //                 }
        //             }
        //         }
        //     }
        // }

        try {
            DB::beginTransaction();
            $address                     = !empty($input['street_address']) ? "{$input['street_address']}, {$input['ward_name']}, {$input['district_name']}, {$input['city_name']}" : null;
            $cart->address               = $address;
            $cart->coupon_delivery_price = null;
            $cart->phone                 = !empty($input['phone']) ? $input['phone'] : null;
            $cart->description           = !empty($input['note']) ? $input['note'] : null;
            $cart->full_name             = !empty($input['full_name']) ? $input['full_name'] : null;
            $cart->email                 = !empty($input['email']) ? $input['email'] : null;
            //            $cart->distributor_id            = !empty($input['distributor_id']) ?$input['distributor_id']: null;
            //            $cart->distributor_code          = !empty($input['distributor_code']) ?$input['distributor_code']: null;
            //            $cart->distributor_name          = !empty($input['distributor_name']) ?$input['distributor_name']: null;
            //            $cart->distributor_phone         = !empty($input['distributor_phone']) ?$input['distributor_phone']: null;
            $cart->order_channel  = !empty($input['order_channel']) ? $input['order_channel'] : null;
            $cart->payment_method = !empty($input['payment_method']) ? $input['payment_method'] : null;
            //            $cart->distributor_city_code     = !empty($input['distributor_city_code']) ?$input['distributor_city_code']: null;
            //            $cart->distributor_district_code = !empty($input['distributor_district_code']) ?$input['distributor_district_code']: null;
            //            $cart->distributor_ward_code     = !empty($input['distributor_ward_code']) ? $input['distributor_ward_code']: null;
            //            $cart->distributor_city_name     = !empty($input['distributor_city_full_name']) ?$input['distributor_city_full_name']: null;
            //            $cart->distributor_district_name = !empty($input['distributor_district_full_name']) ?$input['distributor_ward_full_name']: null;
            //            $cart->distributor_ward_name     = !empty($input['distributor_ward_full_name']) ?$input['distributor_ward_full_name']: null;
            $cart->distributor_lat        = !empty($input['distributor_lat']) ? $input['distributor_lat'] : null;
            $cart->distributor_long       = !empty($input['distributor_long']) ? $input['distributor_long'] : null;
            $cart->distributor_postcode   = !empty($input['distributor_postcode']) ? $input['distributor_postcode'] : null;
            $cart->customer_city_code     = !empty($input['city_code']) ? $input['city_code'] : null;
            $cart->customer_district_code = !empty($input['district_code']) ? $input['district_code'] : null;
            $cart->customer_ward_code     = !empty($input['ward_code']) ? $input['ward_code'] : null;
            $cart->customer_city_name     = !empty($input['city_name']) ? $input['city_name'] : null;
            $cart->customer_district_name = !empty($input['district_name']) ? $input['district_name'] : null;
            $cart->customer_ward_name     = !empty($input['ward_name']) ? $input['ward_name'] : null;
            $cart->customer_lat           = !empty($input['customer_lat']) ? $input['customer_lat'] : null;
            $cart->customer_long          = !empty($input['customer_long']) ? $input['customer_long'] : null;
            $cart->customer_postcode      = !empty($input['customer_postcode']) ? $input['customer_postcode'] : null;
            $cart->street_address         = !empty($input['street_address']) ? $input['street_address'] : null;
            //            $cart->shipping_address_id    = !empty($input['shipping_address_id']) ? $input['shipping_address_id'] : null;
            $cart->address_window_id    = !empty($input['address_window_id']) ? $input['address_window_id'] : null;
            $cart->shipping_method        = !empty($input['shipping_method']) ? $input['shipping_method'] : null;
            $cart->shipping_method_code   = !empty($input['shipping_code']) ? $input['shipping_code'] : null;
            $cart->shipping_method_name   = !empty($input['shipping_name']) ? $input['shipping_name'] : null;
            $cart->shipping_service       = !empty($input['shipping_service']) ? $input['shipping_service'] : null;
            $cart->shipping_note          = !empty($input['shipping_note']) ? $input['shipping_note'] : null;
            $cart->shipping_diff          = !empty($input['shipping_diff']) ? $input['shipping_diff'] : null;
            $cart->service_name           = !empty($input['service_name']) ? $input['service_name'] : null;
            $cart->extra_service          = !empty($input['extra_service']) ? $input['extra_service'] : null;
            $cart->access_trade_id          = !empty($input['access_trade_id']) ? $input['access_trade_id'] : null;
            $cart->access_trade_click_id          = !empty($input['access_trade_click_id']) ? $input['access_trade_click_id'] : null;
            $cart->order_source          = !empty($input['access_trade_click_id']) ? ORDER_SOURCE_TYPE_ACCESS_TRADE : null;
            $cart->ship_fee               = !empty($input['ship_fee']) ? $input['ship_fee'] : 0;
            $cart->estimated_deliver_time = !empty($input['estimated_deliver_time']) ? $input['estimated_deliver_time'] : null;
            $cart->lading_method          = !empty($input['lading_method']) ? $input['lading_method'] : null;
                       $cart->ship_fee_start         = !empty($input['ship_fee_start']) ? $input['ship_fee_start'] : null;
            $cart->cart_info              = json_encode($input);
            // update phí ship
            $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
            $weight          = 0;
            if ($cart->free_item && $cart->free_item != "[]") {
                foreach ($cart->free_item as $item) {
                    foreach ($item['text'] as $value) {
                        $weight += $value['weight'] * ($value['qty_gift'] ?? 1) * $weight_converts[($value['weight_class'])];
                    }
                }
            }
            foreach ($cart['details'] as $detail) {
                if ($detail['product']['gift_item'] && $detail['product']['gift_item'] != "[]") {
                    foreach (json_decode($detail['product']['gift_item']) as $value) {
                        $weight += $value->weight * $weight_converts[$value->weight_class];
                    }
                }
                $weight += $detail['quantity'] * $detail['product']['weight'] * $weight_converts[($detail['product']['weight_class'])];
            }
            $cart->total_weight = $weight ?? 0;
            $product_hub = $cart->details->map(function ($item) {
                return [
                    'code' => $item->product_code,
                    'quantity' => $item->quantity,
                ];
            });
            $distributor                     = $this->getClientDistributor($cart->customer_city_code, $cart->customer_district_code, $cart->customer_ward_code, $request, $weight, null, null, $product_hub);
            //                $distributor == 1 ? $cart->notification_distributor = "Hiện tại các đơn vị giao hàng đã đạt số lượng đơn tối đa trong ngày. Qúy khách có thể quay lại vào ngầy mai." : ($distributor == 2 ? $cart->notification_distributor = "Hiện tại không tìm thấy đơn vị giao cho khối lượng của đơn hàng này." : (empty($distributor) ? $cart->notification_distributor = "Địa điểm của bạn không hổ trợ giao hàng." : null));
            $cart->notification_distributor  = empty($distributor) ? 1 : 0;
            $cart->distributor_id            = $distributor['id'] ?? null;
            $cart->distributor_name          = $distributor['name'] ?? null;
            $cart->distributor_code          = $distributor['code'] ?? null;
            $cart->distributor_phone         = $distributor['phone'] ?? null;
            // $cart->distributor_email         = $distributor['email'] ?? null;
            $cart->distributor_city_name     = $distributor['city_full_name'] ?? null;
            $cart->distributor_city_code     = $distributor['city_code'] ?? null;
            $cart->distributor_district_code = $distributor['district_code'] ?? null;
            $cart->distributor_district_name = $distributor['district_full_name'] ?? null;
            $cart->distributor_ward_code     = $distributor['ward_code'] ?? null;
            $cart->distributor_ward_name     = $distributor['ward_full_name'] ?? null;
            $cart->save();
            if (!empty($cart->shipping_method_code) && !empty($cart->customer_ward_code) && !empty($cart->distributor_ward_code)) {
                $type = $cart->shipping_method_name;
                switch ($type) {
                    case SHIPPING_PARTNER_TYPE_VNP:
                        $result         = VNP::getShipFeeAllService($request, $store_id);
                        $cart->ship_fee = $result[array_search($cart->shipping_service, array_column($result, 'MaDichVu'))]['GiaCuoc'];
                        $cart->ship_fee_start = $result[array_search($cart->shipping_service, array_column($result, 'MaDichVu'))]['GiaCuoc'];
                        $cart->ship_fee_real = $result[array_search($cart->shipping_service, array_column($result, 'MaDichVu'))]['GiaCuoc'];
                        break;
                    case SHIPPING_PARTNER_TYPE_VTP:
                        $result = VTP::getShipFee($request, VTP::getApiToken(), $store_id);
                        $cart->ship_fee = $cart->ship_fee;
                        $cart->ship_fee_start =  $cart->ship_fee_start;
                        $cart->ship_fee_real = (count($result) > 0) ? $result[0]['MONEY_TOTAL'] : 0;
                        break;
                    
                    case SHIPPING_PARTNER_TYPE_GRAB:
                        $service_type   = "INSTANT";
                        $result         = GRAB::getShipFee($request, $store_id, $service_type);
                        // dd($result);
                   
                        if(!empty($result['price'])) {
                            $cart->ship_fee = $cart->ship_fee;
                            $cart->ship_fee_start = $cart->ship_fee_start;
                            $cart->ship_fee_real = $result['price'];
                            $cart->shipping_error = 0;
                            $cart->log_quote_grab = json_encode($result['param']) ?? null;
                            $cart->log_quote_response_grab = json_encode($result['response']) ?? null;
                        }
                        if(empty($result['price'])){
                            $cart->shipping_error = 1;
                            $cart->ship_fee = 0;
                            $cart->ship_fee_start = 0;
                            $cart->ship_fee_real = 0;
                        }
                        $cart->intersection_distance = !empty($result['distance']) ? $result['distance']/1000 : $cart->intersection_distance;
                        break;
                    case SHIPPING_PARTNER_TYPE_DEFAULT:
                        $cart->ship_fee = 0;
                        $cart->ship_fee_start = 0;
                        $cart->ship_fee_real = 0;
                        break;
                }
            }
            if (empty($cart->shipping_method_code)) {
                $cart->ship_fee = null;
            }
            if (!empty($cart->payment_method) && $cart->payment_method == PAYMENT_METHOD_CASH) {
                (float)$price = $cart->total_info[array_search('total', array_column($cart->total_info, 'code'))]['value'];
                if ($cart->shipping_method_code == SHIPPING_PARTNER_TYPE_VTP) {
                    if ((int)$price > 3000000) {
                        $cart->ship_fee = round((float)$cart->ship_fee + ((0.5 / 100) * ((float)$price - 3000000)));
                        $cart->ship_fee_start = $cart->ship_fee;
                    }
                }
                if ($cart->shipping_method_code == SHIPPING_PARTNER_TYPE_GRAB) {
                    if ($price < 5000000) {
                        $cart->ship_fee = $cart->ship_fee + 5000;
                        $cart->ship_fee_start = $cart->ship_fee;
                    }
                    if ($price > 5000000) {
                        $cart->ship_fee = $cart->ship_fee + 15000;
                        $cart->ship_fee_start = $cart->ship_fee;
                    }
                }
            }
            $cart->save();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            // dd($exception->getMessage());
            $response = TM_Error::handle($exception);
            return $this->responseError($response['message']);
        }
        // sleep(3);
        // if ($userId) {
        //     $cart = Cart::model()->with(
        //         'details',
        //         'details.getProduct',
        //         'details.file',
        //         'createdBy',
        //         'createdBy.profile',
        //         'getCity',
        //         'getDistrict',
        //         'getWard',
        //     )->where('user_id', $userId)->first();
        // } else {
        //     if (empty($input['session_id'])) {
        //         $this->responseError(Message::get("V001", Message::get("session_id")));
        //     }
        //     $cart = Cart::model()->with('details', 'details.product')->where('session_id', $input['session_id'])->first();
        // }
        return $this->response->item($cart, new CartTransformer());
    }

    function getClientDistributor($city_code, $district_code, $ward_code, Request $request, $weight, $hub = null, $code = null, $product_hub = null)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
            //
            //            $distributor = $this->model->findDistributor2(
            //                TM::getCurrentUserId(),
            //                $city_code,
            //                $district_code,
            //                $ward_code
            //            );
            //            return response()->json(['data' => $distributor]);
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        if (empty($company_id) || empty($ward_code) || empty($city_code) || empty($district_code)) {
            return ['data' => []];
        }
        $product_codes = [];
        foreach ($product_hub as $product) {
            $product_codes[] =  $product['code'];
        }
        $product_code = "";
        $group_code = USER_GROUP_DISTRIBUTOR_CENTER;

        foreach ($product_codes as $value) {
            $product_code .= "$value,";
        }


        $product_code = trim($product_code, ',');


        $count_product_code = explode(',', $product_code);


        $checkUserProductCode = DB::select("CALL checkUserProductCode(" . "'$product_code'" . ',' . count($count_product_code)  . ")");

        $checkUserProductCode = implode(',', array_pluck($checkUserProductCode, 'user_id'));

        // $distributors = Distributor::with([
        //     'users:code,id,phone,name,group_code,email',
        //     'users.profile:user_id,address,ward_code,district_code,city_code',
        //     'users.profile.ward:code,full_name',
        //     'users.profile.district:code,full_name',
        //     'users.profile.city:code,full_name',
        // ])->select([
        //     'id',
        //     'code',
        //     'name',
        //     'value',
        //     'city_code',
        //     'district_code',
        //     'ward_code',
        // ])->where('city_code', $city_code)
        //     //            ->withCount('countOrder as count_order')
        //     //            ->where('code', "123")
        //     ->where('company_id', $company_id)
        //     ->where('is_active', 1)
        //     ->whereHas('users', function ($q) use ($code, $hub, $weight, $product_hub) {
        //         if (empty($hub)) {
        //             $q->where('group_code', USER_GROUP_DISTRIBUTOR_CENTER);
        //         }
        //         $q->where(function ($b) use ($weight) {
        //             $b->where('qty_remaining_single', '>', 0)->orWhereNull('qty_max_day');
        //         });
        //         $q->where(function ($b) use ($weight) {
        //             $b->where('maximum_volume', '>', $weight)->orWhereNull('maximum_volume');
        //         });
        //         //                if ($hub == 1) {
        //         //                    $q->where('distributor_center_code', $code);
        //         //                }
        //         $q->whereNull('deleted_at');
        //         $q->where('is_active', 1);
        //         foreach ($product_hub as $product) {
        //             $q->whereHas('productHub', function ($q1) use ($product, $hub) {
        //                 $q1->where('product_code', $product['code']);
        //                 if (empty($hub)) {
        //                     $q1->where('limit_date', '>', $product['quantity']);
        //                 }
        //             });
        //         }
        //     })
        //     //        if(empty($distributors->first()) && $hub != 1){
        //     //            return 1;
        //     //        }
        //     //        $distributors = $distributors->whereHas('users', function ($q) use ($code, $hub, $weight) {
        //     //            $q->where(function ($b) use ($weight) {
        //     //                $b->where('maximum_volume', '>', $weight)->orWhereNull('maximum_volume');
        //     //            });
        //     //        });
        //     //        if(empty($distributors->first())&& $hub != 1){
        //     //            return 2;
        //     //        }
        //     //        $distributors = $distributors->whereHas('users',function ($q) use ($code,$hub,$weight){
        //     //                if($hub == 1){
        //     //                    $q->where('distributor_center_code',$code);
        //     //                }
        //     //                $q->whereNull('deleted_at');
        //     //                $q->where('is_active',1);
        //     //            })

        //     ->groupBy(['code', 'name', 'city_code', 'district_code', 'ward_code'])
        //     ->distinct()->get()->toArray();
        // $testDB = DB::select("CALL getClientDistributor2($city_code,$company_id,$product_code,$weight");
        // $testDB = DB::select("CALL testGetClientDistributor1()");


        // dd($testDB);
        // dd($product_codes, $weight,$company_id,$city_code);
        if (empty($hub)) {
            $distributors = DB::select("CALL getClientDistributor1(" . "'$city_code'" . ',' . $company_id . ',' . "'$checkUserProductCode'" . ',' .  "'$group_code'"  . ',' . $weight . ")");
        }
        if (!empty($hub)) {
            $distributors = DB::select("CALL getClientDistributor2(" . "'$city_code'" . ',' . $company_id . ',' . "'$checkUserProductCode'" . ',' . $weight . ")");
        }
        if (empty($distributors)) {
            if (empty($hub)) {
                $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, $weight, 1, null, $product_hub);
                return $distributorsHub;
            }
            if ($hub = 1) {
                return [];
            }
        }

        // dd($distributorsHub);
        //        if($hub == 1 && !empty($distributors)){
        //            return [];
        //        }
        // Find by Ward
//        shuffle($distributors);
        $key = array_search($ward_code, array_column($distributors, 'ward_code'));
        if (is_numeric($key) && $key === 0) {
            $key = (int) $key;
            //            if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
            //                $code             = $distributors[$key]['code'];
            //                $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
            //                    $q->where('code', $code);
            //                })->first();
            //                if (!empty($checkDistributor)) {
            //                    $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, $weight, 1, $distributors[$key]['code']);
            //                    if (!empty($distributorsHub)) {
            //                        return $distributorsHub;
            //                    }
            //                }
            //            }
            $data = $this->getDataDistributorProfile($distributors[$key]);
            return $data;
        }
        // Find by District
        if (empty($key)) {
            $key = array_search($district_code, array_column($distributors, 'district_code'));
        }
        if (is_numeric($key) && $key === 0) {
            $key = (int) $key;
            //            if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
            //                $code             = $distributors[$key]['code'];
            //                $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
            //                    $q->where('code', $code);
            //                })->first();
            //                if (!empty($checkDistributor)) {
            //                    $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, $weight, 1, $distributors[$key]['code']);
            //                    if (!empty($distributorsHub)) {
            //                        return $distributorsHub;
            //                    }
            //                }
            //            }

            $data = $this->getDataDistributorProfile($distributors[$key]);
            return $data;
        }
        // Find by City
        if (empty($key)) {
            $key = array_search($city_code, array_column($distributors, 'city_code'));
        }
        if (is_numeric($key) && $key === 0) {
            $key = (int) $key;
            //            if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
            //                $code             = $distributors[$key]['code'];
            //                $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
            //                    $q->where('code', $code);
            //                })->first();
            //                if (!empty($checkDistributor)) {
            //                    $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, $weight, 1, $distributors[$key]['code']);
            //                    if (!empty($distributorsHub)) {
            //                        return $distributorsHub;
            //                    }
            //                }
            //            }

            $data = $this->getDataDistributorProfile($distributors[$key]);
            return $data;
        }

        if (empty($key) && $hub != 1) {
            $city = CityHasRegion::model()->where('code_city', $city_code)
                ->where('company_id', TM::getCurrentCompanyId())
                ->where('store_id', TM::getCurrentStoreId())->first();
            if (empty($city)) {
                return [];
            };

            return [
                "id"                 => $city->region->distributor_id ?? 0,
                "code"               => $city->region->distributor_code ?? '',
                "name"               => $city->region->distributor_name ?? '',
                "city_code"          => $city->region->city_code ?? '',
                "city_full_name"     => $city->region->city_full_name ?? '',
                "district_code"      => $city->region->district_code ?? '',
                "district_full_name" => $city->region->district_full_name ?? '',
                "ward_code"          => $city->region->ward_code ?? '',
                "ward_full_name"     => $city->region->ward_full_name ?? ''
            ];
            //            return response()->json(['data' => [
            //                "id"            => 0,
            //                "code"          => '',
            //                "name"          => '',
            //                "value"         => '',
            //                "city_code"     => '',
            //                "district_code" => '',
            //                "ward_code"     => ''
            //            ]]);
        }
        $key = (int) $key;
        //        if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
        //            $code             = $distributors[$key]['code'];
        //            $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
        //                $q->where('code', $code);
        //            })->first();
        //            if (!empty($checkDistributor)) {
        //                $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, $weight, 1, $distributors[$key]['code']);
        //                if (!empty($distributorsHub)) {
        //                    return $distributorsHub;
        //                }
        //            }
        //        }

        $data = $this->getDataDistributorProfile($distributors[$key]);
        return $data;
    }

    function getDataDistributorProfile($distributors)
    {
        return [
            "id"                 => $distributors->userId?? "",
            "code"               => $distributors->userCode ?? "",
            "name"               => $distributors->userName ?? "",
            "email"              => $distributors->userEmail ?? "",
            "phone"              => $distributors->userPhone ?? "",
            "city_code"          => $distributors->cityCode ?? "",
            "city_full_name"     => $distributors->cityFullname ?? "",
            "district_code"      => $distributors->dsCode ?? "",
            "district_full_name" => $distributors->dsFullname ?? "",
            "ward_code"          => $distributors->wardCode ?? "",
            "ward_full_name"     => $distributors->wardFullname ?? "",
        ];
    }

    public function create_cart_admin(Request $request) {
        // request: session_id, strore_login_token, user_id
        $input = $request->all();
        $sessions = $request->get('session_id');
        // dd($sessions);
        $user_id = null;
        if(!empty($input['user_id'])){
            $user_id = $input['user_id'];
        }
        $query = Cart::model()->select('id', 'user_id', 'session_id')->where('session_id', $sessions);
        if($user_id){
            $query->where('user_id', $user_id);
        }
        // $query->whereNotNull('user_id');

        $cart = $query->first();
        $user = null;
        if($user_id){
            $user = User::find($user_id);
        }
        // dd($cart);
        if (empty($cart)) {
            $param = [
                'user_id'        => $user_id ?? null,
                'store_id'       => $user->store_id ?? null,
                'company_id'     => $user->company_id ?? null,
                'session_id'     => $sessions,
                'address'        => null,
                'description'    => null,
                'phone'          => null,
                'payment_method' => PAYMENT_METHOD_CASH,
                'receiving_time' => null,
                'order_channel'  => "ADMIN"
            ];
            $cart = $this->model->create($param);
        }

        return response()->json(['id' => $cart->id, 'user_id' => $cart->user_id, 'session_id' => $cart->session_id]);
    }

    public function update_cart_admin(Request $request){
        $input = $request->all();
        (new UpdateCartAdminValidator())->validate($input);
        try {
            //code...
            DB::beginTransaction();
            list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
            (new CartModel())->updateCart($input);
            DB::commit();

            return response()->json([
                'message' => 'Cập nhập giỏ hàng thành công'
            ],200);
        } catch (\Exception $ex) {
            DB::rollBack();
            // $response = TM_Error::handle($ex);
            return $this->response->error($ex->getMessage(), $ex->getCode());
        }
    }

    public function get_cart_admin(Request $request) {
        // list($user_id, $store_id, $group_id, $group_code, $company_id) = DataAdminUser::getInstance()->info();
        // $userId = $request->get('user_id');
        $sessions = $request->get('session_id');
        // app('request')->merge(['group_id' => $group_id]);
        // if ($userId) {
        //     $cart = Cart::where('user_id', $userId)->first();
        //     if (empty($cart)) {
        //         return ['data' => null];
        //     }
        //     // $cart = $this->model->checkWarehouseAdmin($cart);
        //     (new PromotionHandle())->promotionApplyCart($cart);
        // }
        if ($sessions) {
            $cart       = Cart::where('session_id', $sessions)->first();
            if (empty($cart)) {
                return ['data' => null];
            }
            // $cart = $this->model->checkWarehouseAdmin($cart);
            (new PromotionHandle())->promotionApplyCart($cart);
        }
        if (!$sessions) {
            return $this->responseError(Message::get("V003", Message::get("carts")));
        }
        return $this->response->item($cart, new CartTransformer());
    }

    public function update_qty_cart_admin(Request $request, $id) {
        $input = $request->all();
        if (!isset($input['quantity'])) {
            return response()->json([
                'message' => Response::HTTP_UNPROCESSABLE_ENTITY . " " . Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                'errors'  => ['quantity' => [Message::get("V001", Message::get("quantity"))]],
            ], 422);
        }

        $cartDetail = CartDetail::find($id);

        if (empty($cartDetail)) {
            return $this->responseError(Message::get("V003", "ID #$id"));
        }
        if ($input['quantity'] < 0) {
            return $this->responseError(Message::get('V010', Message::get('quantity'), 0));
        }
        $product          = Product::with('warehouse')->find($cartDetail->product_id);
        $productWarehouse = Arr::get($product, 'warehouse', null);
        if (empty($productWarehouse)) {
            return $this->responseError(Message::get("V060"));
        }
        $inventory = Arr::get($product->warehouse, 'quantity', 0);

        if (!empty($input['qr_scan'])) {
            $product->qr_scan += 1;
            $product->save();
        }

        if ($inventory <= 0) {
            return $this->responseError(Message::get("V017"));
        }

        if ($inventory < $input['quantity'] ?? 0) {
            return $this->responseError(Message::get("V013", Message::get("quantity"), $inventory));
        }
        try {
            DB::beginTransaction();
            $quantity             = $input['quantity'];
            $productPrice         = $cartDetail->price;
            $total                = $quantity * $productPrice;
            $cartDetail->quantity = $quantity;
            $cartDetail->total    = $total;
            if ($quantity == 0) {
                $cartDetail->delete();
            }
            $cartDetail->save();
            //            $cart             = Cart::find($cartDetail->cart_id);
            //            $totals           = $this->model->applyPromotion($cart);
            //            $cart->total_info = $totals;
            //            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return [
            'status' => Message::get("carts.update-success")
            //                , 'data' => ['totals' => $totals ?? null]
        ];
    }

    public function admin_add_to_cart(Request $request) {
        $input       = $request->all();
        $session_id  = $input['session_id'] ?? null;
        $userCurrent = $request->get('user_id');

        if (empty($session_id)) {
            return $this->responseError(Message::get("V009", Message::get('session_id')));
        }
        $checkSession = Session::model()->where('session_id', "{$session_id}")->select('id')->first();
        if (empty($checkSession)) {
            return $this->responseError(Message::get("V003", Message::get('session_id')));
        }

        // if ($userCurrent && $session_id) {
        //     $this->mergeCartClientToUser($userCurrent, $session_id);
        //     $result = $this->userAddToCart($request, $session_id);
        // }

        if (!$userCurrent && $session_id) {
            $result = $this->clientAddToCart($request);
        }

        return $result;
    }
}
