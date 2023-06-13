<?php
/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;


use App\City;
use App\Company;
use App\District;
use App\Image;
use App\Jobs\SendHUBMailNewOrderJob;
use App\Order;
use App\OrderDetail;
use App\OrderStatus;
use App\OrderStatusHistory;
use App\Product;
use App\Supports\Message;
use App\Supports\TM_MasterData;
use App\TM;
use App\User;
use App\V1\Models\AbstractModel;
use App\Ward;
use Illuminate\Support\Arr;

class OrderModel extends AbstractModel
{
    public function __construct(Order $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return Order
     * @throws \Exception
     */
    public function upsert($input, $isUpdate = false)
    {
        //Data
        $input['status']           = ORDER_STATUS_NEW;
        $input['code']             = $input['OrderNumber'];
        $input['phone']            = $input['ShippingPhone'];
        $input['customer_name']    = $input['shipping_name'] = !empty($input['ShippingName']) ? $input['ShippingName'] : $input['CustomerName'];
        $input['customer_code']    = $input['CustomerCode'];
        $input['order_type']       = $input['OrderType'];
        $input['approve_by']       = $input['ApprovedBy'];
        $input['shipping_address'] = $input['ShippingAddress'];
        $input['province']         = $input['City'];
        $input['district']         = $input['District'];
        $input['ward']             = $input['Ward'];
        $input['distributor_code'] = $input['DistributorCode'];
        $input['completed_date']   = $input['CompletedDate'];
        $input['approve_date']     = $input['ApprovedDate'];
        $input['order_date']       = $input['OrderDate'];


        $allImageId   = [];
        $shipCity     = City::model()->where('province', $input['province'])->first();
        $shipDistrict = District::model()
            ->where(function ($q) use ($input) {
                if (!empty($input['district'])) {
                    $q->where('district', $input['district']);
                }
            })->first();
        $shipWard     = Ward::model()
            ->where(function ($q) use ($input) {
                if (!empty($input['ward'])) {
                    $q->where('name', $input['ward']);
                }
            })->first();

        $orderStatus = OrderStatus::model()->where([
            'company_id' => TM::getIDP()->company_id,
            'code'       => $input['status'],
        ])->first();

        if (!empty($input['seller_code'])) {
            $checkSellerId = User::model()->where('id', $input['seller_code'])
                ->whereHas('userStores', function ($q) {
                    $q->where('store_id', TM::getIDP()->store_id);
                })->first();
            if (empty($checkSellerId)) {
                throw new \Exception(Message::get("V003", "ID Seller: #" . $input['seller_code']));
            }
        }

        if (empty($orderStatus)) {
            throw new \Exception(Message::get("V002", 'status'));
        }

        $customer = User::model()->where([
            'code'      => $input['customer_code'],
            'type'      => USER_TYPE_CUSTOMER,
            'is_active' => '1',
        ])->first();

        if (empty($customer)) {
            throw new \Exception(Message::get("V003", 'Customer'));
        }


        $distributor = User::model()->where([
            'code'      => $input['distributor_code'],
            'is_active' => '1',
        ])->first();

        if (empty($distributor)) {
            throw new \Exception(Message::get("V003", 'Distributor'));
        }

        if (!empty($input['approve_by'])) {
            $approve_by = User::model()->where([
                'code'      => $input['approve_by'],
                'type'      => USER_TYPE_USER,
                'is_active' => '1',
            ])->first();
        }

        if ($isUpdate) {
            $order               = Order::model()->where('code', $input['code'])->first();
            $oldDistributorEmail = $order->distributor_email ?? null;
            if (!empty($input['cancel_reason'])) {
                if ($input['status'] == ORDER_STATUS_RETURNED || $input['status'] == ORDER_STATUS_CANCELED) {
                    $cancel_reason = $input['cancel_reason'];
                }
            }

            $distributor = User::model()->select(['id', 'code', 'name', 'email'])
                ->where('group_code', USER_GROUP_DISTRIBUTOR)
                ->where(function ($q) use ($input) {
                    if (!empty($input['distributor_code'])) {
                        $q->where('code', $input['distributor_code']);
                    }
                })
                ->first();
            if (empty($distributor)) {
                throw new \Exception(Message::get("V002", Message::get('distributor_code')));
            }
            $allImageId                 = !empty($order->image_ids) ? explode(",", $order->image_ids) : [];
            $order->distributor_id      = $distributor['id'] ?? null;
            $order->distributor_code    = $distributor['code'] ?? null;
            $order->distributor_name    = $distributor['name'] ?? null;
            $order->distributor_email   = $distributor['email'] ?? null;
            $order->order_type          = $input['order_type'] ?? null;
            $order->seller_id           = $seller->id ?? null;
            $order->status              = array_get($input, 'status');
            $order->status_text         = $orderStatus->name;
            $order->customer_id         = $customer->id;
            $order->customer_code       = $customer->code;
            $order->customer_name       = $customer->name;
            $order->customer_phone      = $customer->phone;
            $order->customer_email      = $customer->email;
            $order->partner_id          = array_get($input, 'partner_id');
            $order->note                = array_get($input, 'note');
            $order->phone               = array_get($input, 'phone');
            $order->shipping_address_id = array_get($input, 'shipping_address_id', null);
            $order->shipping_address    = array_get($input, 'shipping_address', null);
            $order->coupon_code         = array_get($input, 'coupon_code');
            $order->payment_method      = array_get($input, 'payment_method');
            $order->payment_status      = array_get($input, 'payment_status', 0);
            $order->updated_date        = !empty($input['updated_date']) ? date("Y-m-d",
                strtotime($input['updated_date'])) : date('Y-m-d');
            $order->created_date        = !empty($input['created_date']) ? date("Y-m-d",
                strtotime($input['created_date'])) : $order->created_date;
            $order->completed_date      = !empty($input['completed_date']) ? date("Y-m-d",
                strtotime($input['completed_date'])) : $order->completed_date;
            $order->delivery_time       = !empty($input['delivery_time']) ? date("Y-m-d H:i:s",
                strtotime($input['delivery_time'])) : $order->delivery_time;
            $order->latlong             = !empty($input['geometry']['latitude']) && !empty($input['geometry']['longitude']) ? $input['geometry']['latitude'] . ", " . $input['geometry']['longitude'] : null;
            $order->lat                 = $input['geometry']['latitude'] ?? null;
            $order->long                = $input['geometry']['longitude'] ?? null;
            $order->cancel_reason       = !empty($cancel_reason) ? $cancel_reason : null;
            $order->approver            = $approve_by->id ?? null;
            $order->store_id            = TM::getIDP()->store_id;
            $order->is_active           = array_get($input, 'is_active', $order->is_active);
            $order->street_address      = array_get($input, 'street_address', $order->street_address);

            $order->shipping_address_ward_code = $shipWard->code ?? null;
            $order->shipping_address_ward_type = $shipWard->type ?? null;
            $order->shipping_address_ward_name = $shipWard->name ?? null;

            $order->shipping_address_district_code = $shipDistrict->code ?? null;
            $order->shipping_address_district_type = $shipDistrict->type ?? null;
            $order->shipping_address_district_name = $shipDistrict->name ?? null;

            $order->shipping_address_city_code = $shipCity->code ?? null;
            $order->shipping_address_city_type = $shipCity->type ?? null;
            $order->shipping_address_city_name = $shipCity->name ?? null;

            $order->shipping_address_phone     = array_get($input, 'phone',
                $order->shipping_address_phone);
            $order->shipping_address_full_name = array_get($input, 'shipping_name',
                $order->shipping_address_full_name);
            $order->discount                   = !empty($input['discount']) ? $input['discount'] : null;
            $order->updated_at                 = date("Y-m-d H:i:s", time());
            $order->updated_by                 = env('VIETTEL_SYNC_NAME');
            $order->save();

            //Send mail update distributor
            $company = Company::model()->where('id', TM::getIDP()->company_id)->first();
            if ($oldDistributorEmail != $distributor['email']) {
                if (!empty($distributor['email']) && env('SEND_EMAIL', 0) == 1) {
                    dispatch(new SendHUBMailNewOrderJob($distributor['email'], [
                        'logo'         => $company->avatar,
                        'support'      => $company->email,
                        'company_name' => $company->name,
                        'order'        => $order,
                        'link_to'      => TM::urlBase("/user/order/" . $order->id),
                    ]));
                }
            }
        } else {
            $district                   = !empty($input['district_code']) ? TM_MasterData::get($input['district_code']) : null;
            $order                      = new Order();
            $order->distributor_id      = $distributor['id'] ?? null;
            $order->distributor_code    = $distributor['code'] ?? null;
            $order->distributor_name    = $distributor['name'] ?? null;
            $order->distributor_email   = $distributor['email'] ?? null;
            $order->code                = $input['code'];
            $order->order_type          = $input['order_type'] ?? null;
            $order->status              = array_get($input, 'status', null);
            $order->status_text         = $orderStatus->name;
            $order->customer_id         = $customer->id;
            $order->customer_code       = $customer->code;
            $order->customer_name       = $customer->name;
            $order->customer_phone      = $customer->phone;
            $order->customer_email      = $customer->email;
            $order->partner_id          = array_get($input, 'partner_id');
            $order->note                = array_get($input, 'note', null);
            $order->phone               = array_get($input, 'phone');
            $order->shipping_address_id = array_get($input, 'shipping_address_id', null);
            $order->shipping_address    = $input['shipping_address'] ?? null;
            $order->coupon_code         = array_get($input, 'coupon_code', null);
            $order->payment_method      = array_get($input, 'payment_method', null);
            $order->payment_status      = array_get($input, 'payment_status', 0);
            $order->updated_date        = !empty($input['updated_date']) ? date("Y-m-d",
                strtotime($input['updated_date'])) : null;
            $order->created_date        = !empty($input['created_date']) ? date("Y-m-d",
                strtotime($input['created_date'])) : date("Y-m-d H:i:s");
            $order->completed_date      = !empty($input['completed_date']) ? date("Y-m-d",
                strtotime($input['completed_date'])) : null;
            $order->delivery_time       = !empty($input['delivery_time']) ? date("Y-m-d H:i",
                strtotime($input['delivery_time'])) : null;
            $order->latlong             = !empty($input['geometry']['latitude']) && !empty($input['geometry']['longitude']) ? $input['geometry']['latitude'] . ", " . $input['geometry']['longitude'] : null;
            $order->lat                 = $input['geometry']['latitude'] ?? null;
            $order->long                = $input['geometry']['longitude'] ?? null;
            $order->cancel_reason       = $input['cancel_reason'] ?? null;
            $order->district_code       = $input['district_code'] ?? null;
            $order->district_fee        = !empty($district->data) ? (float)$district->data : 0;
            $order->approver            = $approve_by->id ?? null;
            $order->store_id            = TM::getIDP()->store_id;
            $order->seller_id           = $seller->id ?? null;
            $order->street_address      = array_get($input, 'street_address', null);

            $order->shipping_address_ward_code = $shipWard->code ?? null;
            $order->shipping_address_ward_type = $shipWard->type ?? null;
            $order->shipping_address_ward_name = $shipWard->name ?? null;

            $order->shipping_address_district_code = $shipDistrict->code ?? null;
            $order->shipping_address_district_type = $shipDistrict->type ?? null;
            $order->shipping_address_district_name = $shipDistrict->name ?? null;

            $order->shipping_address_city_code = $shipCity->code ?? null;
            $order->shipping_address_city_type = $shipCity->type ?? null;
            $order->shipping_address_city_name = $shipCity->name ?? null;

            $order->shipping_address_phone     = array_get($input, 'phone', null);
            $order->shipping_address_full_name = array_get($input, 'shipping_name', null);
            $order->discount                   = !empty($input['discount']) ? $input['discount'] : null;
            $order->is_active                  = array_get($input, 'is_active', 1);
            $order->save();
        }
        // Create|Update Order Detail
        $orderDetailById    = OrderDetail::where('order_id', $order->id)->get()->keyBy('id');
        $originalTotalPrice = 0;
        $totalPrice         = 0;
        $totalDiscount      = 0;
        app('request')->merge(['customer_group_id' => $order->customer->group_id]);
        foreach ($input['details'] as $detail) {
            $detail['product_code'] = $detail['ProductCode'];
            $detail['qty']          = $detail['Quantity'];
            $detail['price']        = $detail['Price'];
            $detail['total']        = $detail['Total'];

            $product = Product::model()->with('priceDetail')->where('code', $detail['product_code'])->first();

            if (empty($product)) {
                throw new \Exception(Message::get("V003",
                    Message::get('products') . ": #{$detail['product_code']}"));
            }

            $realPrice = Arr::get($product->priceDetail, 'price', $product->price);
            $price     = $realPrice;

            $qty = array_get($detail, 'qty', 0);

            $originalTotalPrice += $realPrice * $qty;
            $totalPrice         += $price * $qty;
            $totalDiscount      += $detail['discount'] ?? 0;
            if (empty($detail['id']) || empty($orderDetailById[$detail['id']])) {
                OrderDetail::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'qty'        => $qty,
                    'price'      => $price,
                    'price_down' => 0,
                    'real_price' => $realPrice,
                    'total'      => ($price - (float)($detail['discount'] ?? 0)) * $qty,
                    'note'       => array_get($detail, 'note'),
                    'discount'   => !empty($detail['discount']) ? $detail['discount'] : 0,
                    'is_active'  => 1,
                ]);
                continue;
            }

            $orderDetail             = $orderDetailById[$detail['id']];
            $orderDetail->product_id = $product->id;
            $orderDetail->qty        = $qty;
            $orderDetail->price      = $price;
            $orderDetail->price_down = 0;
            $orderDetail->real_price = $realPrice;
            $orderDetail->total      = ($price - (float)$detail['discount']) * $qty;
            $orderDetail->note       = array_get($detail, 'note');
            $orderDetail->discount   = !empty($detail['discount']) ? $detail['discount'] : 0;
            $orderDetail->updated_at = date('Y-m-d H:i:s', time());
            $orderDetail->updated_by = TM::getIDP()->sync_name;
            $orderDetail->save();

            unset($orderDetailById[$detail['id']]);
        }

        //Update Price Order
        $subTotalPrice          = $totalPrice;
        $order->sub_total_price = $subTotalPrice;
        $order->original_price  = $originalTotalPrice;
        $order->total_price     = $totalPrice;
        $order->total_discount  = $totalDiscount;
        $order->save();


        // Update Price
//        $this->updateOrderPrice($order);

        if (!empty($orderDetailById) && !$orderDetailById->isEmpty()) {
            OrderDetail::whereIn('id', $orderDetailById->pluck('id')->toArray())->delete();
        }

        // Remove Old Image
        if (!empty($input['images'])) {
            foreach ($input['images'] as $image) {
                if (($key = array_search($image['id'], $allImageId)) !== false) {
                    unset($allImageId[$key]);
                }
            }
        }

        if (!empty($allImageId)) {
            // Delete DB
            $images = Image::model()->whereIn('id', $allImageId)->get();
            foreach ($images as $image) {
                // Delete File
                Image::removeImage($image->code);
            }
        }

        // Upload Image
        if (!empty($input['image_uploads'])) {
            foreach ($input['image_uploads'] as $image_upload) {
                $base64 = explode(';base64,', $image_upload);
                if (empty($base64[1])) {
                    throw new \Exception(Message::get("V002", "image"));
                }
                $image            = Image::uploadImage($base64[1]);
                $order->image_ids .= "," . $image->id;
                $order->image_ids = trim($order->image_ids, ",");
                $order->save();
            }
        }

        return $order;
    }

    public function updateOrderStatusHistory(Order $order)
    {
        $orderStatus                           = OrderStatus::model()->where([
            'code'       => $order->status,
            'company_id' => TM::getIDP()->company_id,
        ])->first();
        $orderStatusHistory                    = new OrderStatusHistory();
        $orderStatusHistory->order_id          = $order->id;
        $orderStatusHistory->order_status_id   = $orderStatus->id ?? null;
        $orderStatusHistory->order_status_code = $orderStatus->code ?? null;
        $orderStatusHistory->order_status_name = $orderStatus->name ?? null;
        $orderStatusHistory->created_by        = TM::getIDP()->sync_name;
        $orderStatusHistory->save();
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['date'])) {
            $from = date("Y-m-d", strtotime($input['date']));
            $query->whereDate("created_at", '=', $from);
        }
        if (!empty($input['from']) && !empty($input['to'])) {
            $from = date("Y-m-d", strtotime($input['from']));
            $to   = date("Y-m-d", strtotime($input['to']));
            $query->whereDate("updated_at", '>=', $from)->whereDate("updated_at", '<=', $to);
        }
        if (!empty($input['status'])) {
            if ($input['status'] != 'ALL') {
                $query->where("status", $input['status']);
            }
        }
        if (!empty($input['orderNumber'])) {
            $query->where("code", $input['orderNumber']);
        }
        return $query->get();
    }
}

/*
 {
    "status": "NEW",
    "customer_code": "1599042109",
    "note": "đơn test",
    "approve_by": "dai.idp",
    "shipping_address_city_code": "79",
    "details": [
        {
            "product_code": "8872992980261",
            "qty": 1,
            "total": 154889,
            "price": 155000
        },
        {
            "product_code": "SOFAD06",
            "qty": 1,
            "total": 5200000,
            "price": 5200000
        }
    ]
}
 */