<?php

namespace App\V1\Controllers\Shop;

use App\Category;
use App\Company;
use App\Jobs\SendStoreMailNewOrderJob;
use App\Order;
use App\OrderDetail;
use App\Product;
use App\PromotionProgram;
use App\Role;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\UserStore;
use App\V1\Controllers\BaseController;
use App\V1\Models\OrderModel;
use App\V1\Transformers\Shop\ProductDetailTransformer;
use App\V1\Validators\Shop\OrderCreateValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrderController extends BaseController
{
    /**
     * @var int|null $storeId
     */
    protected $storeId;

    /**
     * @var int|null $companyId
     */
    protected $companyId;

    /**
     * @var int|null $groupId
     */
    protected $groupId;

    /**
     * @var int|null $areaId
     */
    protected $areaId;

    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        if (TM::getCurrentUserId()) {
            $this->storeId   = TM::getCurrentStoreId();
            $this->groupId   = TM::getCurrentGroupId();
            $this->companyId = TM::getCurrentCompanyId();
            $group           = UserGroup::find(TM::getCurrentGroupId());
            if (!empty($group) && $group->is_view) {
                $this->areaId = Auth::user()->area_id;
            }
        } else {
            $authorization = app('request')->header('authorization');
            if (!empty($authorization) && strlen($authorization) == 71) {

                $storeToken = str_replace("Bearer ", "", $authorization);

                $store = Store::select(['id', 'company_id'])->where('token', $storeToken)->first();
                if (!$store) {
                    return ['data' => []];
                }
                $this->storeId   = $store->id;
                $this->companyId = $store->company_id;

                $group = UserGroup::where('company_id', $store->company_id)->where('is_default', 1)->first();
                if (!empty($group)) {
                    $this->groupId = $group->id;
                }
            }
        }
    }

    private function firstOrCreateCustomer($phone)
    {
        $customer = User::where('phone', $phone)
            ->where('store_id', $this->storeId)
            ->first();

        if (empty($customer)) {
            $customer = User::create([
                'code'       => implode('_', [$phone, $this->companyId, $this->storeId]),
                'phone'      => $phone,
                'password'   => Hash::make($phone),
                'type'       => USER_TYPE_CUSTOMER,
                'store_id'   => $this->storeId,
                'company_id' => $this->companyId,
                'group_id'   => $this->groupId,
                'role_id'    => USER_ROLE_GUEST_ID
            ]);

            $company = Company::find($this->companyId);
            $store   = Store::find($this->storeId);
            $role    = Role::find(USER_ROLE_GUEST_ID);

            UserCompany::create([
                'user_id'      => $customer->id,
                'user_code'    => $customer->code,
                'user_name'    => $customer->name,
                'company_id'   => $this->companyId,
                'company_code' => $company->code,
                'company_name' => $company->name,
                'role_id'      => $role->id,
                'role_code'    => $role->code,
                'role_name'    => $role->name
            ]);

            UserStore::create([
                'user_id'      => $customer->id,
                'user_code'    => $customer->code,
                'user_name'    => $customer->name,
                'company_id'   => $this->companyId,
                'company_code' => $company->code,
                'company_name' => $company->name,
                'store_id'     => $this->storeId,
                'store_code'   => $store->code,
                'store_name'   => $store->name,
                'role_id'      => $role->id,
                'role_code'    => $role->code,
                'role_name'    => $role->name
            ]);
        }

        return $customer;
    }

    public function store(Request $request)
    {
        (new OrderCreateValidator())->validate($request->all());

        $request->merge(['group_id' => $this->groupId]);

        $quantity = $request->input('quantity', 1);

        $product = Product::with(['priceDetail'])
            ->where('id', $request->input('product_id'))
            ->first();

        if (empty($product)) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get("product")));
        }

        $promotionProgram              = (new PromotionProgram())->getPromotionProgram($this->companyId);
        $promotionProgramOrderDiscount = (new PromotionProgram())->getPromotionProgram($this->companyId, 'order_discount');
        $code                          = 'O' . Carbon::now()->format('Ymd');
        $orderCode                     = Order::where('code', 'like', $code . '%')
            ->withTrashed()
            ->orderByDesc('code')
            ->first();

        $code = $code . '001';
        if (!empty($orderCode)) {
            $code = ++$orderCode->code;
        }

        $originalPrice = Arr::get($product->priceDetail($product), 'price', $product->price);

        $totalPrice = $originalPrice;

        $totalPrice = (new PromotionProgram())->getPriceProduct($promotionProgram, explode(",", $product->category_ids), $totalPrice);

        $subTotalPrice = $totalPrice;

        if (!empty($promotionProgram)) {
            $promotionProgram->value = ($originalPrice - $totalPrice) * $quantity;
        }

        if (!empty($promotionProgramOrderDiscount)) {
            $pricePromotionOrderDiscount          = (new PromotionProgram())->parsePriceBySaleType($promotionProgramOrderDiscount, $totalPrice);
            $promotionProgramOrderDiscount->value = ($totalPrice - $pricePromotionOrderDiscount) * $quantity;
            $totalPrice                           = $pricePromotionOrderDiscount;
        }

        try {
            DB::beginTransaction();
            $customer = $this->firstOrCreateCustomer($request->input('phone'));
            $order    = Order::create([
                'customer_id'     => $customer->id,
                'code'            => $code,
                'status'          => ORDER_STATUS_NEW,
                'phone'           => $request->input('phone'),
                'original_price'  => $originalPrice * $quantity,
                'sub_total_price' => $subTotalPrice * $quantity,
                'total_price'     => $totalPrice * $quantity,
                'payment_method'  => PAYMENT_METHOD_CASH,
                'created_date'    => Carbon::now(),
                'store_id'        => $request->input('store_id', $this->storeId)
            ]);

            OrderDetail::create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'qty'          => $quantity,
                'price'        => $subTotalPrice,
                'real_price'   => $originalPrice,
                'total'        => $subTotalPrice * $quantity,
                'status'       => ORDER_STATUS_NEW,
                'is_active'    => 1
            ]);

            (new OrderModel())->createPromotionTotal($promotionProgram, $order);
            (new OrderModel())->createPromotionTotal($promotionProgramOrderDiscount, $order);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        try {
            $company = Company::model()->where('id', $this->companyId)->first();
            if(env('SEND_EMAIL', 0) == 1){
                $this->dispatch(new SendStoreMailNewOrderJob($order->store->email_notify, [
                'logo'         => $company->avatar,
                'support'      => $company->email,
                'company_name' => $company->name,
                'order'        => $order
            ]));
            }
            
        } catch (\Exception $exception) {
        }

        return response()->json(['message' => Message::get("R001", Message::get("order"))]);
    }
}