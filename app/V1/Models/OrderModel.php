<?php

/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 09:32 PM
 */

namespace App\V1\Models;


use App\Category;
use App\City;
use App\Company;
use App\District;
use App\Image;
use App\Jobs\SendHUBMailNewOrderJob;
use App\MembershipRank;
use App\Order;
use App\OrderDetail;
use App\OrderStatus;
use App\OrderStatusHistory;
use App\Product;
use App\Profile;
use App\Promotion;
use App\PromotionProgram;
use App\PromotionTotal;
use App\Setting;
use App\Supports\Message;
use App\Supports\TM_MasterData;
use App\TM;
use App\User;
use App\UserSession;
use App\V1\Library\GHN;
use App\V1\Library\GRAB;
use App\V1\Library\NJV;
use App\V1\Library\VNP;
use App\V1\Library\VTP;
use App\Ward;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use App\V1\Models\ShippingOrderModel;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\String\b;

class OrderModel extends AbstractModel
{

    public function __construct(Order $model = null)
    {
        parent::__construct($model);
    }


    public function upsert($input)
    {
        if (TM::getCurrentRole() != USER_ROLE_ADMIN && TM::getCurrentRole() != USER_ROLE_MANAGER && TM::getCurrentRole() != USER_ROLE_SUPERADMIN && TM::getCurrentRole() !=USER_ROLE_MONITOR && TM::getCurrentRole() !=USER_ROLE_SELLER && TM::getCurrentRole() !=USER_ROLE_LEADER) {
            $myUserType = TM::getMyUserType();
            if ($myUserType != USER_TYPE_CUSTOMER) {
                throw new \Exception(Message::get("V003", "ID Customer: #" . TM::getCurrentUserId()));
            }
        }
        if (!empty($input['partner_id'])) {
            $partner = User::find($input['partner_id']);
            if ($partner->type != USER_TYPE_PARTNER) {
                throw new \Exception(Message::get("V003", "ID Partner: #" . $input['partner_id']));
            }
        }
        $allImageId = [];

        if ($input['status'] && !empty($input['shipping_address_city_code'])) {
            $shipCity = City::model()->where('code', $input['shipping_address_city_code'])->first();
        }

        $shipDistrict = District::model()
            ->where(function ($q) use ($input) {
                if (!empty($input['shipping_address_district_code'])) {
                    $q->where('code', $input['shipping_address_district_code']);
                }
            })->first();
        $shipWard = Ward::model()
            ->where(function ($q) use ($input) {
                if (!empty($input['shipping_address_ward_code'])) {
                    $q->where('code', $input['shipping_address_ward_code']);
                }
            })->first();
        $orderStatus = OrderStatus::model()->where([
            'company_id' => TM::getCurrentCompanyId(),
            'code'       => $input['status']
        ])->first();

        if (!empty($input['seller_id'])) {
            $checkSellerId = User::model()->where('id', $input['seller_id'])
                ->whereHas('userStores', function ($q) {
                    $q->where('store_id', TM::getCurrentStoreId());
                })->first();

            if (empty($checkSellerId)) {
                throw new \Exception(Message::get("V003", "ID Seller: #" . $input['seller_id']));
            }
        }

        if (!empty($input['leader_id'])) {
            $checkSellerId = User::model()->where('id', $input['leader_id'])
                ->whereHas('userStores', function ($q) {
                    $q->where('store_id', TM::getCurrentStoreId());
                })->first();

            if (empty($checkSellerId)) {
                throw new \Exception(Message::get("V003", "ID Leader: #" . $input['seller_id']));
            }
        }

        if (empty($orderStatus)) {
            throw new \Exception(Message::get("V002", 'status'));
        }

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $order = Order::find($id);
            $oldDistributorEmail = $order->distributor_email ?? null;
            if (empty($order)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            if (!empty($input['cancel_reason'])) {
                if ($input['status'] == ORDER_STATUS_RETURNED || $input['status'] == ORDER_STATUS_CANCELED) {
                    $cancel_reason = $input['cancel_reason'];
                }
            }

            if (!empty($input['distributor_code'])) {
                $distributor = User::model()->select(['id', 'code', 'name', 'email'])
                    ->where('group_code', USER_GROUP_DISTRIBUTOR)
                    ->where(function ($q) use ($input) {
                        if (!empty($input['distributor_code'])) {
                            $q->where('code', $input['distributor_code']);
                        }
                    })
                    ->first();
            } else {
                $distributor = null;
            }
            if (!empty($input['sub_distributor_code'])) {
                $sub_distributor = User::model()->select(['id', 'code', 'name', 'email'])
                    ->where('group_code', USER_GROUP_DISTRIBUTOR)
                    ->where(function ($q) use ($input) {
                        if (!empty($input['sub_distributor_code'])) {
                            $q->where('code', $input['sub_distributor_code']);
                        }
                    })
                    ->first();
            } else {
                $sub_distributor = null;
            }
            // if (empty($distributor)) {
            //     throw new \Exception(Message::get("V002", Message::get('distributor_code')));
            // }
//            if($input['crm_check'] && $input['crm_check'] == ORDER_STATUS_SELLER_COMPLETE){
//                if(!empty($order->payment_method) && $order->payment_method != PAYMENT_METHOD_CASH && $order->payment_method == 0){
////                    throw new \Exception(Message::get('orders.payment-unpaid'));
//                    return $this->responseError(Message::get('orders.payment-unpaid'));
//                }
//            }
            $allImageId = !empty($order->image_ids) ? explode(",", $order->image_ids) : [];
            $order->hub_id = array_get($input, 'hub_id', $order->hub_id);
            $order->distributor_id = $distributor['id'] ?? $order->distributor_id;
            $order->distributor_code = $distributor['code'] ?? $order->distributor_code;;
            $order->distributor_name = $distributor['name'] ?? $order->distributor_name;
            $order->distributor_email = $distributor['email'] ?? $order->distributor_email;
            $order->sub_distributor_id = $sub_distributor['id'] ?? $order->sub_distributor_id;
            $order->sub_distributor_code = $sub_distributor['code'] ?? $order->sub_distributor_code;
            $order->sub_distributor_name = $sub_distributor['name'] ?? $order->sub_distributor_name;
            $order->sub_distributor_email = $sub_distributor['email'] ?? $order->sub_distributor_email;
            $order->code = array_get($input, 'code');
            $order->order_type = $input['order_type'] ?? null;
            $order->seller_id = !empty($input['seller_id']) ? $input['seller_id'] : $order->seller_id;
            $order->status = array_get($input, 'status');
            $order->status_text = array_get($input, 'status_text', null);
            $order->status_crm = !empty($input['status_crm']) ? $input['status_crm'] : $order->status_crm;
            $order->crm_description = array_get($input, 'crm_description', null);
            $order->crm_check = array_get($input, 'crm_check', null);
            $order->customer_id = array_get($input, 'customer_id');
            $order->partner_id = array_get($input, 'partner_id');
            $order->note = array_get($input, 'note');
            $order->phone = array_get($input, 'phone');
            $order->shipping_address_id = array_get($input, 'shipping_address_id');
            $order->shipping_address = array_get($input, 'shipping_address', null);
            $order->coupon_code = array_get($input, 'coupon_code');
            $order->payment_method = array_get($input, 'payment_method');
            $order->payment_status = array_get($input, 'payment_status', 0);
            $order->updated_date = !empty($input['updated_date']) ? date(
                "Y-m-d",
                strtotime($input['updated_date'])
            ) : date('Y-m-d');
            $order->created_date = !empty($input['created_date']) ? date(
                "Y-m-d",
                strtotime($input['created_date'])
            ) : $order->created_date;
            $order->completed_date = !empty($input['completed_date']) ? date(
                "Y-m-d",
                strtotime($input['completed_date'])
            ) : $order->completed_date;
            $order->delivery_time = !empty($input['delivery_time']) ? date(
                "Y-m-d H:i:s",
                strtotime($input['delivery_time'])
            ) : $order->delivery_time;
            $order->latlong = !empty($input['geometry']['latitude']) && !empty($input['geometry']['longitude']) ? $input['geometry']['latitude'] . ", " . $input['geometry']['longitude'] : null;
            $order->lat = $input['geometry']['latitude'] ?? null;
            $order->long = $input['geometry']['longitude'] ?? null;
            $order->cancel_reason = !empty($cancel_reason) ? $cancel_reason : null;
            $order->approver = array_get($input, 'approver', $order->approver);
            $order->store_id = array_get($input, 'store_id', $order->store_id);
            $order->is_active = array_get($input, 'is_active', $order->is_active);
            $order->street_address = array_get($input, 'street_address', $order->street_address);
            $order->ship_fee = array_get($input, 'ship_fee', $order->ship_fee);

            $order->shipping_address_ward_code = $input['shipping_address_ward_code'] ?? null;
            $order->shipping_address_ward_type = $shipWard->type ?? null;
            $order->shipping_address_ward_name = $shipWard->name ?? null;
            $order->transfer_confirmation = $input['transfer_confirmation'] ?? 0;
            $order->shipping_address_district_code = $input['shipping_address_district_code'] ?? null;
            $order->shipping_address_district_type = $shipDistrict->type ?? null;
            $order->shipping_address_district_name = $shipDistrict->name ?? null;

            $order->shipping_address_city_code = $input['shipping_address_city_code'] ?? null;
            $order->shipping_address_city_type = $shipCity->type ?? null;
            $order->shipping_address_city_name = $shipCity->name ?? null;

            $order->shipping_address_phone = array_get(
                $input,
                'shipping_address_phone',
                $order->shipping_address_phone
            );
            $order->shipping_address_full_name = array_get(
                $input,
                'shipping_address_full_name',
                $order->shipping_address_full_name
            );
            $order->discount = !empty($input['discount']) ? $input['discount'] : null;
            $order->updated_at = date("Y-m-d H:i:s", time());
            $order->updated_by = TM::getCurrentUserId();
            $order->description = !empty($input['description']) ? $input['description'] : null;
            $order->save();

            //Send mail update distributor
            $company = Company::model()->where('id', TM::getCurrentCompanyId())->first();
            if ($distributor != null) {
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
            }
        }
        else {
            $district = !empty($input['district_code']) ? TM_MasterData::get($input['district_code']) : null;
            $distributor = User::model()->select(['id', 'code', 'name', 'email'])
                ->where('group_code', USER_GROUP_DISTRIBUTOR)
                ->where(function ($q) use ($input) {
                    if (!empty($input['distributor_code'])) {
                        $q->where('code', $input['distributor_code']);
                    }
                })
                ->first();
            $sub_distributor = User::model()->select(['id', 'code', 'name', 'email'])
                ->where('group_code', USER_GROUP_DISTRIBUTOR)
                ->where(function ($q) use ($input) {
                    if (!empty($input['sub_distributor_code'])) {
                        $q->where('code', $input['sub_distributor_code']);
                    }
                })
                ->first();
            if (empty($distributor)) {
                throw new \Exception(Message::get("V002", Message::get('distributor_code')));
            }

            $order = new Order();
            $order->code = $input['code'];
            $order->order_type = $input['order_type'] ?? null;
            $order->status = array_get($input, 'status', null);
            $order->status_text = $orderStatus->name;
            $order->status_crm = !empty($input['status_crm']) ? $input['status_crm'] : $order->status_crm;
            $order->crm_description = array_get($input, 'crm_description', null);
            $order->crm_check = array_get($input, 'crm_check', null);
            $order->customer_id = array_get($input, 'customer_id', null);
            $order->distributor_id = $distributor['id'] ?? null;
            $order->distributor_code = $distributor['code'] ?? null;
            $order->distributor_name = $distributor['name'] ?? null;
            $order->distributor_email = $distributor['email'] ?? null;
            $order->sub_distributor_id = $sub_distributor['id'] ?? null;
            $order->sub_distributor_code = $sub_distributor['code'] ?? null;
            $order->sub_distributor_name = $sub_distributor['name'] ?? null;
            $order->sub_distributor_email = $sub_distributor['email'] ?? null;
            $order->partner_id = array_get($input, 'partner_id');
            $order->note = array_get($input, 'note', null);
            $order->phone = array_get($input, 'phone');
            $order->shipping_address_id = array_get($input, 'shipping_address_id', null);
            $order->shipping_address = $input['shipping_address'] ?? null;
            $order->coupon_code = array_get($input, 'coupon_code', null);
            $order->payment_method = array_get($input, 'payment_method', null);
            $order->payment_status = array_get($input, 'payment_status', 0);
            $order->updated_date = !empty($input['updated_date']) ? date(
                "Y-m-d",
                strtotime($input['updated_date'])
            ) : null;
            $order->created_date = !empty($input['created_date']) ? date(
                "Y-m-d",
                strtotime($input['created_date'])
            ) : date("Y-m-d H:i:s", time());
            $order->completed_date = !empty($input['completed_date']) ? date(
                "Y-m-d",
                strtotime($input['completed_date'])
            ) : null;
            $order->delivery_time = !empty($input['delivery_time']) ? date(
                "Y-m-d H:i",
                strtotime($input['delivery_time'])
            ) : null;
            $order->latlong = !empty($input['geometry']['latitude']) && !empty($input['geometry']['longitude']) ? $input['geometry']['latitude'] . ", " . $input['geometry']['longitude'] : null;
            $order->lat = $input['geometry']['latitude'] ?? null;
            $order->long = $input['geometry']['longitude'] ?? null;
            $order->cancel_reason = $input['cancel_reason'] ?? null;
            $order->district_code = $input['district_code'] ?? null;
            $order->district_fee = !empty($district->data) ? (float)$district->data : 0;
            $order->approver = array_get($input, 'approver', null);
            $order->store_id = array_get($input, 'store_id', null);
            $order->seller_id = array_get($input, 'seller_id', null);
            $order->leader_id = array_get($input, 'leader_id', null);
            $order->street_address = array_get($input, 'street_address', null);

            $order->shipping_address_ward_code = $input['shipping_address_ward_code'] ?? null;
            $order->shipping_address_ward_type = $shipWard->type ?? null;
            $order->shipping_address_ward_name = $shipWard->name ?? null;

            $order->shipping_address_district_code = $input['shipping_address_district_code'] ?? null;
            $order->shipping_address_district_type = $shipDistrict->type ?? null;
            $order->shipping_address_district_name = $shipDistrict->name ?? null;

            $order->shipping_address_city_code = $input['shipping_address_city_code'];
            $order->shipping_address_city_type = $shipCity->type;
            $order->shipping_address_city_name = $shipCity->name;

            $order->shipping_address_phone = array_get($input, 'shipping_address_phone', null);
            $order->shipping_address_full_name = array_get($input, 'shipping_address_full_name', null);
            $order->discount = !empty($input['discount']) ? $input['discount'] : null;
            $order->is_active = array_get($input, 'is_active', 1);
            $order->updated_at = date("Y-m-d H:i:s", time());
            $order->save();
        }
        // Create|Update Order Detail
        // $orderDetailById = OrderDetail::where('order_id', $order->id)->get()->keyBy('id');
        // $originalTotalPrice = 0;
        // $totalPrice = 0;
        // $totalDiscount = 0;
        app('request')->merge(['customer_group_id' => $order->customer->group_id]);

        // foreach ($input['details'] as $detail) {

        //     $product = Product::with('priceDetail')->find($detail['product_id']);

        //     if (empty($product)) {
        //         throw new \Exception(Message::get(
        //             "V003",
        //             Message::get('products') . "ID: #{$detail['product_id']}"
        //         ));
        //     }

        //     $realPrice = Arr::get($product->priceDetail($product), 'price', $product->price);
        //     $price = $realPrice;

        //     $qty = array_get($detail, 'qty', 0);

        //     $originalTotalPrice += $realPrice * $qty;
        //     $totalPrice += $price * $qty;
        //     $totalDiscount += $detail['discount'] ?? 0;
        //     if (empty($orderDetailById[$detail['id']])) {
        //         OrderDetail::create([
        //             'order_id'   => $order->id,
        //             'product_id' => $detail['product_id'],
        //             'qty'        => $qty,
        //             'save_qty'   => $qty,
        //             'price'      => $price,
        //             'price_down' => 0,
        //             'real_price' => $realPrice,
        //             'total'      => ($price - (float)$detail['discount']) * $qty,
        //             'note'       => array_get($detail, 'note'),
        //             'discount'   => !empty($detail['discount']) ? $detail['discount'] : 0,
        //             'is_active'  => 1
        //         ]);
        //         continue;
        //     }

        //     $orderDetail = $orderDetailById[$detail['id']];
        //     $orderDetail->product_id = $product->id;
        //     $orderDetail->qty = $qty;
        //     $orderDetail->save_qty = array_get($detail, 'save_qty', $orderDetail->save_qty);
        //     $orderDetail->price = $price;
        //     $orderDetail->price_down = 0;
        //     $orderDetail->real_price = $realPrice;
        //     $orderDetail->total = ($price - (float)$detail['discount']) * $qty;
        //     $orderDetail->note = array_get($detail, 'note');
        //     $orderDetail->discount = !empty($detail['discount']) ? $detail['discount'] : 0;
        //     $orderDetail->updated_at = date('Y-m-d H:i:s', time());
        //     $orderDetail->updated_by = TM::getCurrentUserId();
        //     $orderDetail->save();

        //     unset($orderDetailById[$detail['id']]);
        // }

        $order->save();

        // if (!empty($orderDetailById) && !$orderDetailById->isEmpty()) {
        //     OrderDetail::whereIn('id', $orderDetailById->pluck('id')->toArray())->delete();
        // }


        return $order;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $roleCurrentGroup = TM::getCurrentRoleGroup();
        if ($roleCurrentGroup != USER_ROLE_GROUP_ADMIN) {
            switch ($roleCurrentGroup) {
                case USER_ROLE_GROUP_MANAGER:
                    if(TM::info()['role_code'] == USER_ROLE_DISTRIBUTION) {
                        $distributorCode   = User::model()->where('distributor_center_id', TM::info()['id'])->pluck('code')->toArray();
                        $distributorCode[] = TM::info()['code'];
                        $query->where(function ($q) use ($distributorCode) {
                            $q->whereIn($this->getTable() . '.distributor_code', $distributorCode);
                            $q->where($this->getTable() . '.status_crm','!=', ORDER_STATUS_CRM_PENDING);
                        });
                    }
                    if(TM::info()['role_code'] == USER_ROLE_EMPLOYEE){
                        switch(TM::info()['parent_role']) {
                            case USER_ROLE_DISTRIBUTION:
                                $distributorCode   = User::model()->where('distributor_center_code', TM::info()['parent_code'])->pluck('code')->toArray();
                                $distributorCode[] = TM::info()['parent_code'];
                                $query->where(function ($q) use ($distributorCode) {
                                    $q->whereIn($this->getTable() . '.distributor_code', $distributorCode);
                                    $q->where($this->getTable() . '.status_crm','!=', ORDER_STATUS_CRM_PENDING);
                                });
                                break;
                            case USER_GROUP_DISTRIBUTOR:
                                $query->where(function ($q) {
                                    $q->where([
                                        $this->getTable() . '.distributor_code' => TM::info()['parent_code'],
                                        $this->getTable() . '.status_crm' => ORDER_STATUS_CRM_ADAPPROVED,
                                    ]);
                                });
                                break;
                            case USER_GROUP_HUB:
                                $query->where(function ($q) {
                                    $q->where([
                                        $this->getTable() . '.distributor_code' => TM::info()['parent_code'],
                            //                                        $this->getTable() . '.status_crm' => ORDER_STATUS_CRM_APPROVED,
                                    ]);
                                    $q->where( $this->getTable() . '.status_crm','!=',ORDER_STATUS_CRM_PENDING);
                                });
                                break;
                            default:
                                $query->where(function ($q) {
                                    $q->where([
                                        $this->getTable() . '.distributor_code' => TM::info()['parent_code'],
                                        $this->getTable() . '.status_crm' => ORDER_STATUS_CRM_APPROVED,
                                    ]);
                                });
                        }
                    }
                    break;

                case USER_ROLE_GROUP_EMPLOYEE:
                    if(TM::info()['role_code'] == USER_ROLE_LEADER){
                        $query->where(function ($q) {
                            $q->where([
                                $this->getTable() . '.leader_id' => TM::info()['id'],
//                                $this->getTable() . '.status_crm'=> ORDER_STATUS_CRM_PENDING,
                                ]);
                        });
                    }
                    if(TM::info()['role_code'] == USER_ROLE_SELLER){
                        $query->where(function ($q) {
                            $q->where([
                                $this->getTable() . '.seller_id' => TM::info()['id'],
//                                $this->getTable() . '.status_crm'=> ORDER_STATUS_CRM_PENDING,
                                ]);
                        });
                    }
                    if(TM::info()['role_code'] == USER_GROUP_DISTRIBUTOR){
                        $query->where(function ($q) {
                            $q->where([
                                $this->getTable() . '.distributor_code' => TM::info()['code'],
                                ]);
                            $q->where(function ($q2) {
                                $q2->whereIn($this->getTable() . '.status_crm', [ORDER_STATUS_CRM_ADAPPROVED, ORDER_STATUS_CRM_APPROVED]);
                                $q2->orWhere($this->getTable() . '.payment_status', 1);
                            });
                            
                        });
                    }
                    if(TM::info()['role_code'] == USER_GROUP_HUB){
                        $query->where(function ($q) {
                            $q->where([
                                $this->getTable() . '.distributor_code' => TM::info()['code'],
                    //                                $this->getTable() . '.status_crm' => ORDER_STATUS_CRM_APPROVED,
                            ]);
                            $q->where( $this->getTable() . '.status_crm','!=',ORDER_STATUS_CRM_PENDING);
                        });
                    }
                    break;
                case USER_ROLE_GROUP_SELLER:
                    $query->where(function ($q) {
                        $q->where($this->getTable() . '.seller_id', TM::getCurrentUserId());
                    });
                    break;
                default:
                    $query->where(function ($q) {
                        $q->where($this->getTable() . '.customer_id', TM::getCurrentUserId());
                    });
                    break;
            }
        }
        if ($roleCurrentGroup == USER_ROLE_ADMIN) {
//            if(TM::info()['role_code'] == USER_ROLE_MONITOR ){
//                $query->where('status_crm', ORDER_STATUS_CRM_PENDING);
//            }
            if (!empty($input['seller_id'])) {
                $query->where('seller_id', $input['seller_id']);
            }
            if (!empty($input['seller_id'])) {
                $query->where('seller_id', $input['seller_id']);
            }
            if (!empty($input['payment_code'])) {
                $query->where('payment_code', $input['payment_code']);
            }
            if (!empty($input['shipping_note'])) {
                $query->where('shipping_note', $input['shipping_note']);
            }
            if (!empty($input['customer_id'])) {
                $query->where('customer_id', $input['customer_id']);
            }
            if (!empty($input['partner_id'])) {
                $query->where('partner_id', $input['partner_id']);
            }
            if (!empty($input['distributor_id'])) {
                $query->where('distributor_id', $input['distributor_id']);
            }
            if (!empty($input['distributor_code'])) {
                $query->where('distributor_code', "like", "%{$input['distributor_code']}%");
            }
            if (!empty($input['distributor_name'])) {
                $query->where('distributor_name', "like", "%{$input['distributor_name']}%");
            }
        }
        if (!empty($input['status_crm'])) {
            $query->where('status_crm', $input['status_crm']);
        }
        // search weight
        if (!empty($input['weight_to']) && !empty($input['weight_form'])) {
            $query->whereBetween('total_weight', [$input['weight_to'],$input['weight_form']]);
        }
        if(!empty($input['weight_form'])){
            $query->where('total_weight',">",$input['weight_to']);
        }
        if(!empty($input['weight_to'])){
            $query->where('total_weight',"<",$input['weight_to']);
        }
        // end search weight
        if (!empty($input['lading_method'])) {
            $query->where('lading_method', $input['lading_method']);
        }
        if (!empty($input['customer_group_code'])) {
            $query->whereHas('customer', function ($q) use ($input) {
                $q->where('group_code', 'like', "%{$input['customer_group_code']}%");
            });
        }
        if (!empty($input['from']) && !empty($input['to'])) {
            // $from = date("Y-m-d H:i:s", strtotime($input['from']));
            // $to = date("Y-m-d H:i:s", strtotime($input['to']));
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['from'])))->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['to'])));
        }
        if (!empty($input['customer_group_name'])) {
            $query->whereHas('customer', function ($q) use ($input) {
                $q->where('group_code', 'like', "%{$input['customer_group_name']}%");
            });
        }
        if (!empty($input['code'])) {
            $order_code = $input['code'];
            $query = $query->where(function ($q) use ($order_code) {
                $order_code = explode(" ", $order_code);
                foreach ($order_code as $item) {
                    $q->orwhere('code', $item);
                }
            });
        }

        if (!empty($input['order_type'])) {
            $query->where('order_type', 'like', "%{$input['order_type']}%");
        }

        if (!empty($input['store_id'])) {
            $query->where('store_id', $input['store_id']);
        }

        if (!empty($input['shipping_method_code'])) {
            $query->where('shipping_method_code', $input['shipping_method_code']);
        }

        if (!empty($input['district_code'])) {
            $query->where('shipping_address_district_code', $input['district_code']);
        }

        if (!empty($input['ward_code'])) {
            $query->where('shipping_address_ward_code', $input['ward_code']);
        }

        if (!empty($input['city_code'])) {
            $query->where('shipping_address_city_code', $input['city_code']);
        }

        if (isset($input['transfer_confirmation'])) {
            $query->where('transfer_confirmation', $input['transfer_confirmation']);
        }

        if (!empty($input['outvat'])) {
            $query->where('outvat', $input['outvat']);
        }
        if (!empty($input['payment_method'])) {
            $query->where('payment_method', $input['payment_method']);
        }

        if (isset($input['payment_status'])) {
            $query->where('payment_status', $input['payment_status']);
        }

        if (!empty($input['order_channel'])) {
            $query->where('order_channel', $input['order_channel']);
        }

        if (!empty($input['customer_name'])) {
            $query->whereHas('customer.profile', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['customer_name']}%");
            });
        }

        if (!empty($input['product_code'])) {
            $query->whereHas('details', function ($q) use ($input) {
                $q->where('product_code', 'like', "%{$input['product_code']}%");
            });
        }

        if (!empty($input['product_name'])) {
            $query->whereHas('details', function ($q) use ($input) {
                $q->where('product_name', 'like', "%{$input['product_name']}%");
            });
        }

        if (!empty($input['customer_phone'])) {
            $query->whereHas('customer', function ($q) use ($input) {
                $q->where('phone', 'like', "%{$input['customer_phone']}%");
            });
            $query->orWhere('customer_phone', 'like', "%{$input['customer_phone']}%");
        }
        if (!empty($input['partner_name'])) {
            $query->whereHas('partner.profile', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['partner_name']}%");
            });
        }
        if (!empty($input['created_date'])) {
            $query->whereDate('created_date', date('Y-m-d', strtotime($input['created_date'])));
        }
        if (!empty($input['completed_date'])) {
            $query->whereDate('completed_date', date('Y-m-d', strtotime($input['completed_date'])));
        }

        if (!empty($input['status_text'])) {
            $status_text =  urldecode($input['status_text']);
            $query->where('status_text', 'like', "%{$status_text}%");
        }

        if (!empty($input['status'])) {
            $query->whereHas('getStatus', function ($q) use ($input) {
                $arrayStatus = explode(",", $input['status']);
                foreach ($arrayStatus as $key => $item) {
                    if ($key < 1) {
                        $q->where('status', 'like', "%{$item}%");
                    } else {
                        $q->orWhere('status', 'like', "%{$item}%");
                    }
                }
            });
        }
        if (!empty($input['exclude_status'])) {
            $result = explode(',', $input['exclude_status']);
            $query->whereNotIn('status', $result);
        }
        if (isset($input['is_active'])) {
            $query->where('is_active', $input['is_active']);
        }

        if (isset($input['qr_scan'])) {
            $query->where('qr_scan', $input['qr_scan']);
        }

        if (isset($input['is_seller'])) {
            if($input['is_seller'] == 0){
                $query->whereNull('seller_id');
            }
            if($input['is_seller'] != 0){
                $query->whereNotNull('seller_id');
            }
        }
    
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function searchMyOrder($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with)->where('store_id', TM::getCurrentStoreId());
        $this->sortBuilder($query, $input);
        if (TM::getMyUserType() == USER_TYPE_PARTNER) {
            $query->where($this->getTable() . '.partner_id', TM::getCurrentUserId());
        } elseif (TM::getMyUserType() == USER_TYPE_CUSTOMER) {
            $query->where(function ($q) {
                $q->where($this->getTable() . '.customer_id', TM::getCurrentUserId())
                    ->orWhere(function ($q2) {
                        $q2->where('distributor_id', TM::getCurrentUserId())
                            ->where('distributor_status', '!=', 'CANCELED');
                    });
            });
        }

        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['customer_name'])) {
            $query->whereHas('customer.profile', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['customer_name']}%");
            });
        }
        if (!empty($input['partner_name'])) {
            $query->whereHas('partner.profile', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['partner_name']}%");
            });
        }
        if (!empty($input['created_date'])) {
            $query->whereDate('created_date', date('Y-m-d', strtotime($input['created_date'])));
        }
        if (!empty($input['completed_date'])) {
            $query->whereDate('completed_date', date('Y-m-d', strtotime($input['completed_date'])));
        }
        if (!empty($input['status'])) {
            $query->where('status', 'like', "%{$input['status']}%");
        }
        if (!empty($input['status_text'])) {
            $query->where('status_text', 'like', "%{$input['status_text']}%");
        }
        if (!empty($input['exclude_status'])) {
            $result = explode(',', $input['exclude_status']);
            $query->whereNotIn('status', $result);
        }
        if (!empty($input['from']) && !empty($input['to'])) {
            if (count(explode('-', $input['to'])) < 3) {
                $input['to'] = date("Y-m-t", strtotime($input['to']));
            }
            $query = $query->where('updated_at', '>=', date('Y-m-d', strtotime($input['from'])) . " 00:00:00")
                ->where('updated_at', '<=', date('Y-m-d', strtotime($input['to'])) . " 23:59:59");
        }
        if (isset($input['is_active'])) {
            $query->where('is_active', $input['is_active']);
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function getPartnerForOrder($lat, $long, $deniedIds = [], $limit = 10)
    {
        if (empty($lat) || empty($long)) {
            return null;
        }
        $partners = Profile::selectRaw("us.socket_id, profiles.user_id, profiles.full_name, profiles.phone, profiles.lat, profiles.long, 
        6378137 * (
            2 * ATAN2(
                SQRT(
                    SIN(((profiles.lat - $lat)* PI() / 180) / 2) * SIN(((profiles.lat - $lat)* PI() / 180) / 2) +
                    COS(($lat)* PI() / 180) * COS((profiles.lat)* PI() / 180) * SIN(((profiles.long - $long) * PI() / 180) / 2) * SIN(((profiles.long - $long) * PI() / 180) / 2)
                ), SQRT(
                    1 - (
                        SIN(((profiles.lat - $lat)* PI() / 180) / 2) * SIN(((profiles.lat - $lat)* PI() / 180) / 2) +
                        COS(($lat)* PI() / 180) * COS((profiles.lat)* PI() / 180) * SIN(((profiles.long - $long) * PI() / 180) / 2) * SIN(((profiles.long - $long) * PI() / 180) / 2)
                    )
                )
            )
        ) as distance")
            ->join('users as u', 'u.id', '=', 'profiles.user_id')
            ->join('user_sessions as us', function ($q) {
                $q->on('us.user_id', '=', 'u.id')
                    ->where('us.deleted', '0')
                    //                    ->whereNotNull('us.socket_id')
                    ->whereNotNull('us.device_token')
                    //                    ->where('us.socket_id', '!=', '')
                    ->where('us.device_token', '!=', '')
                    ->whereNull('us.deleted_at');
            })
            ->leftJoin('orders as o', function ($q1) {
                $q1->on('o.partner_id', '=', 'profiles.user_id')
                    ->where('o.status', '!=', ORDER_STATUS_COMPLETED);
            })
            ->whereNull('u.deleted_at')
            ->whereNotNull('profiles.lat')
            ->whereNotNull('profiles.long')
            ->where('profiles.lat', '!=', '0')
            ->where('profiles.long', '!=', '0')
            ->where('profiles.ready_work', 1)
            ->where('profiles.is_active', 1)
            ->where('u.type', USER_TYPE_PARTNER)
            ->where('u.partner_type', USER_PARTNER_TYPE_PERSONAL)
            //            ->whereNull('o.id')
            //            ->where(function ($q) {
            //                $q->whereNull('o.id');
            //            })
            ->having('distance', '<=', '50000')
            ->orderByRaw("distance ASC");

        if ($deniedIds) {
            $partners = $partners->whereNotIn('u.id', $deniedIds);
        }

        if ($limit) {
            if ($limit === 1) {
                return $partners->where(function ($q) {
                    $q->whereNull('o.denied_ids')
                        ->orWhere(DB::raw("FIND_IN_SET(profiles.user_id, o.denied_ids)"), "0");
                })->first();
            }

            $partners = $partners->limit($limit);
        }

        return $partners->get();
    }

    public function getEnterpriseForOrder($lat, $long, $deniedIds = [], $areaIds = [], $limit = 10)
    {
        if (empty($lat) || empty($long)) {
            return null;
        }
        /** @var Model $partners */
        $partners = Profile::selectRaw("us.socket_id, profiles.user_id, profiles.full_name, profiles.phone, profiles.lat, profiles.long, 
        6378137 * (
            2 * ATAN2(
                SQRT(
                    SIN(((profiles.lat - $lat)* PI() / 180) / 2) * SIN(((profiles.lat - $lat)* PI() / 180) / 2) +
                    COS(($lat)* PI() / 180) * COS((profiles.lat)* PI() / 180) * SIN(((profiles.long - $long) * PI() / 180) / 2) * SIN(((profiles.long - $long) * PI() / 180) / 2)
                ), SQRT(
                    1 - (
                        SIN(((profiles.lat - $lat)* PI() / 180) / 2) * SIN(((profiles.lat - $lat)* PI() / 180) / 2) +
                        COS(($lat)* PI() / 180) * COS((profiles.lat)* PI() / 180) * SIN(((profiles.long - $long) * PI() / 180) / 2) * SIN(((profiles.long - $long) * PI() / 180) / 2)
                    )
                )
            )
        ) as distance")
            ->join('users as u', 'u.id', '=', 'profiles.user_id')
            ->join('user_sessions as us', function ($q) {
                $q->on('us.user_id', '=', 'u.id')
                    ->where('us.deleted', '0')
                    //                    ->whereNotNull('us.socket_id')
                    ->whereNotNull('us.device_token')
                    //                    ->where('us.socket_id', '!=', '')
                    ->where('us.device_token', '!=', '')
                    ->whereNull('us.deleted_at');
            })
            ->leftJoin('orders as o', function ($q1) {
                $q1->on('o.partner_id', '=', 'profiles.user_id')
                    ->where('o.status', '!=', ORDER_STATUS_COMPLETED);
            })
            ->whereNull('u.deleted_at')
            ->whereNotNull('profiles.lat')
            ->whereNotNull('profiles.long')
            ->where('profiles.lat', '!=', '0')
            ->where('profiles.long', '!=', '0')
            ->where('profiles.ready_work', 1)
            ->where('profiles.is_active', 1)
            ->where('u.type', USER_TYPE_PARTNER)
            ->where('u.partner_type', USER_PARTNER_TYPE_ENTERPRISE)
            //            ->whereNull('o.id')
            //            ->where(function ($q) {
            //                $q->whereNull('o.id');
            //            })
            ->having('distance', '<=', '50000')
            ->orderByRaw("distance ASC");

        if ($deniedIds) {
            $partners = $partners->whereNotIn('u.id', $deniedIds);
        }

        if ($areaIds) {
            $partners = $partners->whereIn('p.area_id', $areaIds);
            $limit = count($areaIds);
        }

        if ($limit) {
            if ($limit === 1) {
                return $partners->where(function ($q) {
                    $q->whereNull('o.denied_ids')
                        ->orWhere(DB::raw("FIND_IN_SET(profiles.user_id, o.denied_ids)"), "0");
                })->first();
            }

            $partners = $partners->limit($limit);
        }

        return $partners->get();
    }

    public function createPromotionTotal(array $promotionProgram, $order, $cart = null)
    {
        if (!empty($promotionProgram)) {
            $checkPromotion = PromotionTotal::where('promotion_id', $promotionProgram['id'])
                ->where('order_id', $order->id)
                ->first();
            if (empty($checkPromotion)) {
                PromotionTotal::create([
                    'cart_id'                 => !empty($cart) ? $cart->id ?? null : null,
                    'order_id'                => $order->id,
                    'order_code'              => $order->code,
                    'order_customer_id'       => object_get($order, 'customer.id'),
                    'order_customer_code'     => object_get($order, 'customer.code'),
                    'order_customer_name'     => object_get($order, 'customer.name'),
                    'promotion_id'            => $promotionProgram['id'],
                    'promotion_code'          => $promotionProgram['code'],
                    'promotion_name'          => $promotionProgram['name'],
                    'promotion_type'          => $promotionProgram['promotion_type'],
                    'promotion_act_approval'  => $promotionProgram['act_approval'],
                    'promotion_act_type'      => $promotionProgram['act_type'],
                    'value'                   => $promotionProgram['value'] ?? 0,
                    'promotion_act_price'     => $promotionProgram['act_price'],
                    'promotion_act_sale_type' => $promotionProgram['act_sale_type'],
                    'company_id'              => !empty($cart) ? $cart->company_id : TM::getCurrentCompanyId(),
                    'store_id'                => $order->store_id
                ]);
                 // Update last_buy
              if ($promotionProgram['act_type'] == 'last_buy') {
                  $last_order = Order::model()->where('customer_id', object_get($order, 'customer.id'))->where('status', "COMPLETED")->orderBy('created_at', 'desc')->first();
                  if(!empty($last_order)) {
                      $usedOrderLastBuy   = PromotionProgram::where('id', $promotionProgram['id'])->value('order_used');
                      $usedOrderLastBuy   = !empty($usedOrderLastBuy) ? explode(",", $usedOrderLastBuy) : [];
                      $usedOrderLastBuy[] = $last_order->id;
                      PromotionProgram::where('id', $promotionProgram['id'])->update([
                          'order_used' => !empty($usedOrderLastBuy) ? implode(",", $usedOrderLastBuy) : null
                      ]);
                  }
            }
                $usedOrder = PromotionProgram::where('id', $promotionProgram['id'])->value('used_order');
                $usedOrder = !empty($usedOrder) ? explode(",", $usedOrder) : [];
                $usedOrder[] = $order->code;
                PromotionProgram::where('id', $promotionProgram['id'])->update([
                    'used'       => DB::raw('used + 1'),
                    'used_order' =>!empty($usedOrder) ? implode(",", $usedOrder) : null
                ]);
            } else {
                $checkPromotion->value = $promotionProgram['value'] ?? 0;
                $checkPromotion->promotion_act_type = $promotionProgram['act_type'];
                $checkPromotion->promotion_act_price = $promotionProgram['act_price'];
                $checkPromotion->promotion_act_sale_type = $promotionProgram['act_sale_type'];
                $checkPromotion->save();
            }
        }
    }

    public function updateProductSold(Order $order)
    {
        $order_details = OrderDetail::model()->where('order_id', $order->id)
        ->select('id','product_id','qty')->get();

        foreach ($order_details as $order_detail) {
            $this->increaseSoldCount($order_detail->product_id, $order_detail->qty);
        }

        return true;
    }

    private function updateOrderPrice(Order &$order)
    {
        // Price from Product
        $products = [];
        foreach ($order->details as $detail) {
            $products[$detail->product_id] = $products[$detail->product_id] ?? 0;
            $products[$detail->product_id] += $detail->qty;
        }

        $time = time();
        $allProduct = Product::model()->whereIn('id', array_keys($products))->get();

        $totalPrice = 0;
        $totalDiscount = 0;
        $originalPrice = 0;
        foreach ($allProduct as $item) {
            $originalPrice += ($item->price * $products[$item->id]);
            $from = $item->price_down ? strtotime($item->down_from) : null;
            $to = $item->price_down ? strtotime($item->down_to) : null;
            $price = $from && $to && $from <= $time && $to >= $time ? $item->price_down : $item->price;
            $totalPrice += ($price * $products[$item->id]);
        }

        $price = $totalPrice;

        // With Coupon Code
        if (!empty($order->coupon_code) && ($coupon = $this->getCoupon($order->coupon_code, $time))) {
            $discount = $totalPrice * $coupon->discount_rate / 100;
            if ($coupon->max_discount > 0 && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }
            $totalPrice -= $discount;
            $totalDiscount = $discount;
        }

        // Ship Fee
        $totalPrice += $order->district_fee;

        $order->original_price = $originalPrice;
        $order->price = $price;
        $order->total_price = $totalPrice;
        $order->total_discount = $totalDiscount;
        $order->save();
    }

    private function getCoupon($code, $time = null)
    {
        $order = Order::model()->where('coupon_code', $code)->where(
            'customer_id',
            TM::getCurrentUserId()
        )->get()->toArray();
        if (count($order) > 1) {
            return false;
        }

        $time = date('Y-m-d H:i:s', $time ? $time : time());
        $promotion = Promotion::model()->where('code', $code)
            ->where('from', '<=', $time)
            ->where('to', '>=', $time)
            ->where('discount_rate', '>', '0')
            ->first();
        if (empty($promotion)) {
            return false;
        }

        $customer = TM::info();
        if (
            ($promotion->type == "POINT" && $promotion->point < $customer->point)
            || ($promotion->type == "RANKING" && $promotion->ranking_id != $customer->ranking_id)
        ) {
            return false;
        }

        return $promotion;
    }

    private function getPriceDown($productId, $date = null)
    {
        if ($date == null) {
            $date = date("Y-m-d H:i:s", time());
        }

        $date = date("Y-m-d H:i:s", strtotime($date));
        $product = Product::model()->where('id', $productId);

        $tmp = $product;
        $tmp = $tmp->first();
        if ($tmp) {
            $priceDown = $tmp->price_down;
        }

        $product = $product->where('down_from', '<=', $date)->where('down_to', '>=', $date)->first();
        if (!empty($product)) {
            $realPrice = $product->price_down;
        }

        return [
            'price_down' => $priceDown ?? 0,
            'real_price' => $realPrice ?? 0,
        ];
    }

    private function increaseSoldCount($product, $number)
    {
        if (!is_object($product)) {
            $currentSoldCount = Product::find($product);

            DB::table('products')
                ->where('id', $product)
                ->whereNull('deleted_at')
                ->update(['sold_count' => $currentSoldCount->sold_count + $number]);
        }

        return true;
    }

    public function searchByPhone($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (isset($input['phone'])) {
            $query->where('customer_phone', $input['phone']);
        }
        if (!empty($input['order_code'])) {
            $query->where('code', 'like', "%{$input['order_code']}%");
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function updateOrderStatusHistory(Order $order)
    {
        $orderStatus = OrderStatus::model()->where([
            'code'       => $order->status,
            'company_id' => TM::getCurrentCompanyId() ?? $order->company_id
        ])->select('id','code','name')->first();
        $param = [
            'order_id'          => $order->id,
            'order_status_id'   => $orderStatus->id ?? null,
            'order_status_code' => $orderStatus->code ?? null,
            'order_status_name' => $orderStatus->name ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
            'created_by'        => TM::getCurrentUserId() ?? $order->customer_id
        ];
        OrderStatusHistory::insert($param);
//        $orderStatusHistory                    = new OrderStatusHistory();
//        $orderStatusHistory->order_id          = $order->id;
//        $orderStatusHistory->order_status_id   = $orderStatus->id ?? null;
//        $orderStatusHistory->order_status_code = $orderStatus->code ?? null;
//        $orderStatusHistory->order_status_name = $orderStatus->name ?? null;
//        $orderStatusHistory->created_by        = 1;
//        $orderStatusHistory->save();
    }

    public function getTotalSalesForCustomers($input)
    {
        $orders = Order::model()->select([
            'u.id as customer_id',
            'u.code as customer_code',
            'u.name as customer_name',
            'u.type as customer_type',
            'u.group_code as customer_group',
            'u.level_number as customer_level',
            'u.user_level1_id',
            'u.user_level2_ids',
            'u.user_level3_ids',
            DB::raw('sum(orders.total_price) - sum(orders.total_discount) as total_sales'),
        ])
            ->join('users as u', 'u.id', '=', 'orders.customer_id')
            ->where('orders.order_type', ORDER_TYPE_AGENCY)
            ->where('orders.status', ORDER_STATUS_COMPLETED)
            ->where('u.company_id', TM::getCurrentCompanyId())
            ->where('u.type', USER_TYPE_CUSTOMER)
            ->where('u.group_code', USER_GROUP_AGENT)
            ->where('u.is_active', '1');

        if (!empty($input['user_ids'])) {
            $orders = $orders->whereIn('customer_id', $input['user_ids']);
        }
        if (!empty($input['from'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '>=',
                date('Y-m-d', strtotime($input['from']))
            );
        }

        if (!empty($input['to'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '<=',
                date('Y-m-d', strtotime($input['to']))
            );
        }

        $orders = $orders->groupBy('orders.customer_id')->get()->toArray();

        return $orders;
    }

    public function getTotalSalesForCustomerByCategory($input)
    {
        $orders = Order::model()->select([
            'u.id as customer_id',
            'u.code as customer_code',
            'u.name as customer_name',
            'u.type as customer_type',
            'u.group_code as customer_group',
            'u.level_number as customer_level',
            'u.user_level1_id',
            'u.user_level2_ids',
            'u.user_level3_ids',
            'od.product_category',
            DB::raw('sum(od.qty * od.price) - sum(od.qty * od.discount) as total_sales'),
        ])
            ->join('users as u', 'u.id', '=', 'orders.customer_id')
            ->leftJoin('order_details as od', 'od.order_id', '=', 'orders.id')
            ->where('orders.order_type', ORDER_TYPE_AGENCY)
            ->where('orders.status', ORDER_STATUS_COMPLETED)
            ->where('u.company_id', TM::getCurrentCompanyId())
            ->where('u.type', USER_TYPE_CUSTOMER)
            ->where('u.group_code', USER_GROUP_AGENT)
            ->where('u.is_active', '1');

        if (!empty($input['user_ids'])) {
            $orders = $orders->whereIn('customer_id', $input['user_ids']);
        }
        if (!empty($input['from'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '>=',
                date('Y-m-d', strtotime($input['from']))
            );
        }

        if (!empty($input['to'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '<=',
                date('Y-m-d', strtotime($input['to']))
            );
        }

        if (!empty($input['category_ids'])) {
            $orders = $orders->whereIn('od.product_category', $input['category_ids']);
        }

        $orders = $orders->groupBy(['orders.customer_id', 'od.product_category'])->get()->toArray();
        //        $orders = $orders->groupBy(['orders.customer_id'])->get()->toArray();

        return $orders;
    }

    public function getAllCategoryOrder($input)
    {
        $orders = Order::model()->select([
            'od.product_category'
        ])
            ->join('users as u', 'u.id', '=', 'orders.customer_id')
            ->leftJoin('order_details as od', 'od.order_id', '=', 'orders.id')
            ->where('orders.order_type', ORDER_TYPE_AGENCY)
            ->where('orders.status', ORDER_STATUS_COMPLETED)
            ->where('u.company_id', TM::getCurrentCompanyId())
            ->where('u.type', USER_TYPE_CUSTOMER)
            ->where('u.group_code', USER_GROUP_AGENT)
            ->where('u.is_active', '1');

        if (!empty($input['user_ids'])) {
            $orders = $orders->whereIn('customer_id', $input['user_ids']);
        }
        if (!empty($input['from'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '>=',
                date('Y-m-d', strtotime($input['from']))
            );
        }

        if (!empty($input['to'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '<=',
                date('Y-m-d', strtotime($input['to']))
            );
        }

        $orders = $orders->groupBy('od.product_category')->get()->toArray();

        return $orders;
    }

    /**
     * @param $orders
     * @return mixed
     */
    public function getCustomerSale($orders)
    {
        foreach ($orders as $i => $order) {
            $orders[$i]['total_group_sales'] = $order['total_sales'];
            switch ($order['customer_level']) {
                case 1:
                    if (!empty($order['user_level2_ids'])) {
                        $userLevel2 = explode(",", $order['user_level2_ids']);
                        $orderTmp2s = $orders;
                        foreach ($orderTmp2s as $item2) {
                            if (in_array($item2['customer_id'], $userLevel2)) {
                                $orders[$i]['total_group_sales'] += $item2['total_sales'];
                            }
                        }
                    }

                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                            }
                        }
                    }
                    break;
                case 2:
                    $child_commission = 0;
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                            }
                        }
                    }
                    break;
                case 3:
                    break;
            }
        }

        return $orders;
    }

    public function calculateCustomerCommission($orders, $setting)
    {
        $user = User::model()->select([
            'id',
            'level_number',
            'user_level1_id',
            'user_level2_ids',
            'user_level3_ids'
        ])
            ->whereIn('id', array_column($orders, 'customer_id'))
            ->get()->pluck(null, 'id')->toArray();

        $orderById = [];
        foreach ($orders as $order) {
            $orderById[$order['customer_id']] = $order;
        }

        foreach ($orders as $i => $order) {
            $orders[$i]['total_group_sales'] = $order['total_sales'];
            switch ($order['customer_level']) {
                case 1:
                    if (!empty($order['user_level2_ids'])) {
                        $userLevel2 = explode(",", $order['user_level2_ids']);
                        $orderTmp2s = $orders;
                        foreach ($orderTmp2s as $item2) {
                            if (in_array($item2['customer_id'], $userLevel2)) {
                                $orders[$i]['total_group_sales'] += $item2['total_sales'];
                            }
                        }
                    }

                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                            }
                        }
                    }
                    $commission = $this->getLevel1Commission($orders[$i], $user, $orderById, $setting);
                    $orders[$i]['commission'] = $commission['commission'];
                    $orders[$i]['rate'] = $commission['rate'];
                    break;
                case 2:
                    $child_commission = 0;
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                $child_commission += $this->getCommission(
                                    $item3['total_sales'],
                                    $setting
                                )['commission'];
                            }
                        }
                    }
                    $commission = $this->getCommission($orders[$i]['total_group_sales'], $setting);
                    $orders[$i]['commission'] = $commission['commission'] - $child_commission;
                    $orders[$i]['rate'] = $commission['rate'];
                    break;
                case 3:
                    $commission = $this->getCommission($orders[$i]['total_group_sales'], $setting);
                    $orders[$i]['commission'] = $commission['commission'];
                    $orders[$i]['rate'] = $commission['rate'];
                    break;
            }
        }

        return $orders;
    }

    public function calculateCustomerReferralCommission2($orders, $commission_setting, $referral_setting)
    {
        $settingValue = $referral_setting['value'];
        $referral_setting = $referral_setting['data'];

        $user = User::model()->select([
            'id',
            'level_number',
            'user_level1_id',
            'user_level2_ids',
            'user_level3_ids'
        ])
            ->whereIn('id', array_column($orders, 'customer_id'))
            ->get()->pluck(null, 'id')->toArray();

        $orderById = [];
        foreach ($orders as $order) {
            $orderById[$order['customer_id']] = $order;
        }

        foreach ($orders as $i => $order) {
            $orders[$i]['total_group_sales'] = $order['total_sales'];
            $orders[$i]['total_referral_sales'] = 0;
            switch ($order['customer_level']) {
                case 1:
                    $branch = 0;
                    $orderLevel2TotalSale = [];
                    if (!empty($order['user_level2_ids'])) {
                        $userLevel2 = explode(",", $order['user_level2_ids']);
                        $orderTmp2s = $orders;
                        foreach ($orderTmp2s as $item2) {
                            if (in_array($item2['customer_id'], $userLevel2)) {
                                $orders[$i]['total_group_sales'] += $item2['total_sales'];
                                $orders[$i]['total_referral_sales'] += $item2['total_sales'];
                                $orderLevel2TotalSale[$item2['customer_id']] = $item2['total_sales'];
                            }
                        }
                    }

                    $userLevelTotalSale = [];
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                $orders[$i]['total_referral_sales'] += $item3['total_sales'];
                                $userLevelTotalSale[$item3['user_level2_ids']] = $userLevelTotalSale[$item3['user_level2_ids']] ?? 0;
                                $userLevelTotalSale[$item3['user_level2_ids']] += $item3['total_sales'];
                            }
                        }
                    }

                    foreach ($orderLevel2TotalSale as $level2Id => $total) {
                        if (($userLevelTotalSale[$level2Id] ?? 0) >= $settingValue) {
                            $orders[$i]['total_referral_sales'] -= ($userLevelTotalSale[$level2Id] ?? 0);
                        }

                        if (($total + ($userLevelTotalSale[$level2Id] ?? 0)) >= $settingValue) {
                            $branch++;
                        }
                    }

                    // Commission
                    $total_commission = $this->getLevel1Commission($orders[$i], $user, $orderById, $commission_setting);
                    $orders[$i]['commission'] = $total_commission['commission'];
                    $orders[$i]['rate'] = $total_commission['rate'];

                    // Referral Commission
                    $referral_commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orders[$i]['total_referral_sales'],
                        $branch,
                        $referral_setting
                    );
                    $orders[$i]['referral_commission'] = $referral_commission['commission'];
                    $orders[$i]['referral_rate'] = $referral_commission['rate'];

                    // Format
                    $total_group_commission = $this->getCommission(
                        $orders[$i]['total_group_sales'],
                        $commission_setting
                    );
                    $total_member_commission = $total_group_commission['commission'] - $orders[$i]['commission'];
                    $orders[$i]['commission_group_rate'] = number_format($orders[$i]['total_group_sales']) . " x " . $orders[$i]['rate'] . "% = " . number_format($total_group_commission['commission']);
                    $orders[$i]['commission_person_rate'] = number_format($total_group_commission['commission']) . " - " . number_format($total_member_commission) . " = " . number_format($orders[$i]['commission']);
                    $orders[$i]['commission_referral'] = number_format($orders[$i]['total_referral_sales']) . " x " . number_format($orders[$i]['referral_rate']) . "% = " . number_format($referral_commission['commission']);
                    $orders[$i]['total_commission'] = number_format($orders[$i]['commission']) . " + " . number_format($referral_commission['commission']) . " = " . number_format($orders[$i]['commission'] + $referral_commission['commission']);
                    break;
                case 2:
                    $branch = 0;
                    $child_commission = 0;
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_referral_sales'] += $item3['total_sales'];
                                if ($item3['total_sales'] >= $settingValue) {
                                    $branch++;
                                }
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                $child_commission += $this->getCommission(
                                    $item3['total_sales'],
                                    $commission_setting
                                )['commission'];
                            }
                        }
                    }

                    // Commission
                    $total_commission = $this->getCommission($orders[$i]['total_group_sales'], $commission_setting);
                    $orders[$i]['commission'] = $total_commission['commission'] - $child_commission;
                    $orders[$i]['rate'] = $total_commission['rate'];

                    // Referral Commission
                    $referral_commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orders[$i]['total_referral_sales'],
                        $branch,
                        $referral_setting
                    );
                    $orders[$i]['referral_commission'] = $referral_commission['commission'];
                    $orders[$i]['referral_rate'] = $referral_commission['rate'];

                    // Format
                    $total_group_commission = $this->getCommission(
                        $orders[$i]['total_group_sales'],
                        $commission_setting
                    );
                    $total_member_commission = $total_group_commission['commission'] - $orders[$i]['commission'];
                    $orders[$i]['commission_group_rate'] = number_format($orders[$i]['total_group_sales']) . " x " . $orders[$i]['rate'] . "% = " . number_format($total_group_commission['commission']);
                    $orders[$i]['commission_person_rate'] = number_format($total_group_commission['commission']) . " - " . number_format($total_member_commission) . " = " . number_format($orders[$i]['commission']);
                    $orders[$i]['commission_referral'] = empty($orders[$i]['referral_rate']) ? '' : number_format($orders[$i]['total_referral_sales']) . " x " . number_format($orders[$i]['referral_rate']) . "% = " . number_format($referral_commission['commission']);
                    $orders[$i]['total_commission'] = number_format($orders[$i]['commission']) . " + " . number_format($referral_commission['commission']) . " = " . number_format($orders[$i]['commission'] + $referral_commission['commission']);
                    break;
                case 3:
                    break;
            }
        }

        return $orders;
    }

    public function calculateCustomerReferralCommission($orders, $setting)
    {
        $settingValue = $setting['value'];
        $setting = $setting['data'];

        $orderById = [];
        foreach ($orders as $order) {
            $orderById[$order['customer_id']] = $order;
        }

        foreach ($orders as $i => $order) {
            $orders[$i]['total_group_sales'] = 0;
            switch ($order['customer_level']) {
                case 1:
                    $branch = 0;
                    $orderLevel2TotalSale = [];
                    if (!empty($order['user_level2_ids'])) {
                        $userLevel2 = explode(",", $order['user_level2_ids']);
                        $orderTmp2s = $orders;
                        foreach ($orderTmp2s as $item2) {
                            if (in_array($item2['customer_id'], $userLevel2)) {
                                $orders[$i]['total_group_sales'] += $item2['total_sales'];
                                $orderLevel2TotalSale[$item2['customer_id']] = $item2['total_sales'];
                            }
                        }
                    }

                    $userLevelTotalSale = [];
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                $userLevelTotalSale[$item3['user_level2_ids']] = $userLevelTotalSale[$item3['user_level2_ids']] ?? 0;
                                $userLevelTotalSale[$item3['user_level2_ids']] += $item3['total_sales'];
                            }
                        }
                    }

                    foreach ($orderLevel2TotalSale as $level2Id => $total) {
                        if (($userLevelTotalSale[$level2Id] ?? 0) >= $settingValue) {
                            $orders[$i]['total_group_sales'] -= ($userLevelTotalSale[$level2Id] ?? 0);
                        }

                        $level2TotalSale = $total + ($userLevelTotalSale[$level2Id] ?? 0);
                        if ($level2TotalSale >= $settingValue) {
                            $branch++;
                        } else {
                            $orders[$i]['total_group_sales'] -= $level2TotalSale;
                        }
                    }

                    $commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orders[$i]['total_group_sales'],
                        $branch,
                        $setting
                    );
                    $orders[$i]['commission'] = $commission['commission'];
                    $orders[$i]['rate'] = $commission['rate'];
                    break;
                case 2:
                    $branch = 0;
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                if ($item3['total_sales'] >= $settingValue) {
                                    $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                    $branch++;
                                }
                            }
                        }
                    }
                    $commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orders[$i]['total_group_sales'],
                        $branch,
                        $setting
                    );
                    $orders[$i]['commission'] = $commission['commission'];
                    $orders[$i]['rate'] = $commission['rate'];
                    break;
                case 3:
                    break;
            }
        }

        return $orders;
    }

    /**
     * @param $input
     * @return mixed
     */
    public function getTotalSalesDetail($input)
    {
        $orders = Order::model()->select([
            'orders.code as order_code',
            DB::raw('DATE_FORMAT(orders.created_at, "%d/%m/%Y") as order_date'),
            DB::raw('CONCAT("#", p.code) as product_code'),
            'p.name as product_name',
            'od.qty as qty',
            'od.price as price',
            'od.total as total',
        ])
            ->join('users as u', 'u.id', '=', 'orders.customer_id')
            ->join('order_details as od', 'od.order_id', '=', 'orders.id')
            ->join('products as p', 'p.id', '=', 'od.product_id')
            ->where('orders.order_type', ORDER_TYPE_AGENCY)
            ->where('orders.status', ORDER_STATUS_COMPLETED)
            ->where('u.code', $input['customer_code'])
            ->where('u.company_id', TM::getCurrentCompanyId())
            ->where('u.type', USER_TYPE_CUSTOMER)
            ->where('u.group_code', USER_GROUP_AGENT)
            ->where('u.is_active', '1');

        if (!empty($input['from'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '>=',
                date('Y-m-d', strtotime($input['from']))
            );
        }

        if (!empty($input['to'])) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '<=',
                date('Y-m-d', strtotime($input['to']))
            );
        }

        $orders = $orders->orderBy('orders.code')->orderBy('p.name')->get()->toArray();

        return $orders;
    }

    public function calculateForTree($orders, $commission_setting, $referral_setting, $from, $to)
    {
        $settingValue = $referral_setting['value'];
        $referral_setting = $referral_setting['data'];

        $user = User::model()->select([
            'id',
            'level_number',
            'user_level1_id',
            'user_level2_ids',
            'user_level3_ids'
        ])
            ->whereIn('id', array_column($orders, 'customer_id'))
            ->get()->pluck(null, 'id')->toArray();

        $orderById = [];
        foreach ($orders as $order) {

            if (!empty($orderById[$order['customer_id']])) {
                $tmp = $orderById[$order['customer_id']];
                $tmp['total_sales'] = $orderById[$order['customer_id']]['total_sales'] + $order['total_sales'];
                $tmp['product_category'] = $orderById[$order['customer_id']]['product_category'] . "," . $order['product_category'];
                unset($orderById[$order['customer_id']]);
                $order = $tmp;
            }
            $orderById[$order['customer_id']] = $order;
        }
        $orderTemp = $orderById;

        foreach ($orderTemp as $i => $order) {
            $orderTemp[$i]['total_group_sales'] = $order['total_sales'];
            $orderTemp[$i]['total_referral_sales'] = 0;
            switch ($order['customer_level']) {
                case 1:
                    $branch = 0;
                    $orderLevel2TotalSale = [];

                    if (!empty($order['user_level2_ids'])) {
                        $userLevel2 = explode(",", $order['user_level2_ids']);
                        //                        $orderTmp2s = $orders;
                        $orderTmp2s = $orderTemp;
                        foreach ($orderTmp2s as $item2) {
                            if (in_array($item2['customer_id'], $userLevel2)) {
                                $orderTemp[$i]['total_group_sales'] += $item2['total_sales'];
                                $orderTemp[$i]['total_referral_sales'] += $item2['total_sales'];
                                $orderLevel2TotalSale[$item2['customer_id']] = $item2['total_sales'];
                            }
                        }
                    }

                    $userLevelTotalSale = [];
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        //                        $orderTmp3s = $orders;
                        $orderTmp3s = $orderTemp;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orderTemp[$i]['total_group_sales'] += $item3['total_sales'];
                                $orderTemp[$i]['total_referral_sales'] += $item3['total_sales'];
                                $userLevelTotalSale[$item3['user_level2_ids']] = $userLevelTotalSale[$item3['user_level2_ids']] ?? 0;
                                $userLevelTotalSale[$item3['user_level2_ids']] += $item3['total_sales'];
                            }
                        }
                    }

                    foreach ($orderLevel2TotalSale as $level2Id => $total) {
                        if (($userLevelTotalSale[$level2Id] ?? 0) >= $settingValue) {
                            $orderTemp[$i]['total_referral_sales'] -= ($userLevelTotalSale[$level2Id] ?? 0);
                        }
                        $level2TotalSale = $total + ($userLevelTotalSale[$level2Id] ?? 0);
                        if ($level2TotalSale >= $settingValue) {
                            $branch++;
                        } else {
                            //                            $orders[$i]['total_group_sales'] -= $level2TotalSale;
                            $orderTemp[$i]['total_referral_sales'] -= $level2TotalSale;
                        }
                    }
                    // Commission
                    $total_commission = $this->getLevel1Commission($orderTemp[$order['customer_id']], $user, $orderById, $commission_setting, $from, $to);
                    $orderTemp[$i]['commission'] = $total_commission['commission'];
                    $orderTemp[$i]['rate'] = $total_commission['rate'];
                    // Commission
                    //                    $total_commission         = $this->getLevel1Commission($orders[$i], $user, $orderById, $commission_setting);
                    //                    $orders[$i]['commission'] = $total_commission['commission'];
                    //                    $orders[$i]['rate']       = $total_commission['rate'];

                    // Referral Commission
                    $referral_commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orderTemp[$i]['total_referral_sales'],
                        $branch,
                        $referral_setting
                    );
                    $orderTemp[$i]['referral_commission'] = $referral_commission['commission'];
                    $orderTemp[$i]['referral_rate'] = $referral_commission['rate'];

                    // Format
                    //                    $proCat   = $orderTemp[$i]['product_category'];
                    //                    $proCatEx = explode(',', $proCat);
                    //
                    //                    if (count($proCatEx) != 1) {
                    //                        $total_group_commission = 0;
                    //                        foreach ($proCatEx as $categoryId) {
                    //                            $totalSale = $this->totalSalesForCustomerByCategory($orderTemp[$i]['customer_id'], $categoryId, $from, $to)['total_sales'] ?? 0;
                    //                            $cat       = Category::find($categoryId);
                    //                            if (!empty($cat)) {
                    //                                $commission_setting = json_decode($cat->data, true);
                    //                            }
                    //                            $total_group_commission += $this->getCommission($totalSale, $commission_setting)['commission'];
                    //                        }
                    //                    } else {
                    //                        $cat = Category::find($proCat);
                    //                        if (!empty($cat)) {
                    //                            $commission_setting = json_decode($cat->data, true);
                    //                        }
                    //                        $total_group_commission = $this->getCommission($orderTemp[$i]['total_group_sales'], $commission_setting)['commission'];
                    //                    }
                    //
                    //                    $total_member_commission = $total_group_commission - $orderTemp[$i]['commission'];
                    //                    $orderTemp[$i]['commission_group_rate']  = number_format($orderTemp[$i]['total_group_sales']) . " x " . $orderTemp[$i]['rate'] . "% = " . number_format($total_group_commission);
                    //                    $orderTemp[$i]['commission_person_rate'] = number_format($total_group_commission) . " - " . number_format($total_member_commission) . " = " . number_format($orderTemp[$i]['commission']);
                    //                    $orderTemp[$i]['commission_referral']    = number_format($orderTemp[$i]['total_referral_sales']) . " x " . number_format($orderTemp[$i]['referral_rate']) . "% = " . number_format($referral_commission['commission']);
                    //                    $orderTemp[$i]['total_commission']       = number_format($orderTemp[$i]['commission']) . " + " . number_format($referral_commission['commission']) . " = " . number_format($orderTemp[$i]['commission'] + $referral_commission['commission']);
                    break;
                case 2:
                    $branch = 0;
                    $child_commission = 0;
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orderTemp;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                if ($item3['total_sales'] >= $settingValue) {
                                    $orderTemp[$i]['total_referral_sales'] += $item3['total_sales'];
                                    $branch++;
                                }

                                $orderTemp[$i]['total_group_sales'] += $item3['total_sales'];
                                $proCat = $orderTemp[$item3['customer_id']]['product_category'];
                                $proCatEx = explode(',', $proCat);
                                if (count($proCatEx) != 1) {
                                    $child_commission = 0;
                                    foreach ($proCatEx as $categoryId) {
                                        $totalSale = $this->totalSalesForCustomerByCategory($item3['customer_id'], $categoryId, $from, $to)['total_sales'] ?? 0;
                                        $cat = Category::find($categoryId);
                                        if (!empty($cat)) {
                                            $commission_setting = json_decode($cat->data, true);
                                        }
                                        $child_commission += $this->getCommission($totalSale, $commission_setting)['commission'] ?? 0;
                                    }
                                } else {
                                    $cat = Category::find($proCat);
                                    if (!empty($cat)) {
                                        $commission_setting = json_decode($cat->data, true);
                                    }
                                    $totalSale = $this->totalSalesForCustomerByCategory($orderTemp[$i]['customer_id'], $proCat, $from, $to)['total_sales'] ?? 0;
                                    $child_commission += $this->getCommission($totalSale, $commission_setting)['commission'] ?? 0;
                                }
                            }
                        }
                    }
                    // Commission
                    $proCat = $orderTemp[$i]['product_category'];
                    $proCatEx = explode(',', $proCat);

                    if (count($proCatEx) != 1) {
                        $total_commission = 0;
                        $total_rate = 0;
                        foreach ($proCatEx as $categoryId) {
                            $totalSale = $this->totalSalesForCustomerByCategory($orderTemp[$i]['customer_id'], $categoryId, $from, $to)['total_sales'] ?? 0;
                            $cat = Category::find($categoryId);
                            if (!empty($cat)) {
                                $commission_setting = json_decode($cat->data, true);
                            }
                            $total_commission += $this->getCommission($totalSale, $commission_setting)['commission'];
                            $total_rate += $this->getCommission($totalSale, $commission_setting)['rate'];
                        }
                    } else {
                        $cat = Category::find($proCat);
                        if (!empty($cat)) {
                            $commission_setting = json_decode($cat->data, true);
                        }
                        $total_commission = $this->getCommission($orderTemp[$i]['total_group_sales'], $commission_setting)['commission'];
                        $total_rate = $this->getCommission($orderTemp[$i]['total_group_sales'], $commission_setting)['rate'];
                    }

                    $orderTemp[$i]['commission'] = $total_commission - $child_commission;
                    $orderTemp[$i]['rate'] = $total_rate;

                    // Commission
                    //                    $total_commission         = $this->getCommission($orders[$i]['total_group_sales'], $commission_setting);
                    //                    $orders[$i]['commission'] = $total_commission['commission'] - $child_commission;
                    //                    $orders[$i]['rate']       = $total_commission['rate'];

                    // Referral Commission
                    $referral_commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orderTemp[$i]['total_referral_sales'],
                        $branch,
                        $referral_setting
                    );
                    $orderTemp[$i]['referral_commission'] = $referral_commission['commission'];
                    $orderTemp[$i]['referral_rate'] = $referral_commission['rate'];

                    // Format
                    //                    $proCat   = $orderTemp[$i]['product_category'];
                    //                    $proCatEx = explode(',', $proCat);
                    //
                    //                    if (count($proCatEx) != 1) {
                    //                        $total_group_commission = 0;
                    //                        $total_rate             = 0;
                    //                        foreach ($proCatEx as $categoryId) {
                    //                            $totalSale = $this->totalSalesForCustomerByCategory($orderTemp[$i]['customer_id'], $categoryId, $from, $to)['total_sales'] ?? 0;
                    //                            $cat       = Category::find($categoryId);
                    //                            if (!empty($cat)) {
                    //                                $commission_setting = json_decode($cat->data, true);
                    //                            }
                    //                            $total_group_commission += $this->getCommission($totalSale, $commission_setting)['commission'];
                    //                        }
                    //                    } else {
                    //                        $cat = Category::find($proCat);
                    //                        if (!empty($cat)) {
                    //                            $commission_setting = json_decode($cat->data, true);
                    //                        }
                    //                        $total_group_commission = $this->getCommission($orderTemp[$i]['total_group_sales'], $commission_setting)['commission'];
                    //                    }
                    ////                    $total_group_commission               = $this->getCommission($orderTemp[$i]['total_group_sales'],
                    ////                        $commission_setting);
                    //                    $total_member_commission = $total_group_commission - $orderTemp[$i]['commission'];
                    //                    $orderTemp[$i]['commission_group_rate']  = number_format($orderTemp[$i]['total_group_sales']) . " x " . $orderTemp[$i]['rate'] . "% = " . number_format($total_group_commission);
                    //                    $orderTemp[$i]['commission_person_rate'] = number_format($total_group_commission) . " - " . number_format($total_member_commission) . " = " . number_format($orderTemp[$i]['commission']);
                    //                    $orderTemp[$i]['commission_referral']    = empty($orderTemp[$i]['referral_rate']) ? '' : number_format($orderTemp[$i]['total_referral_sales']) . " x " . number_format($orderTemp[$i]['referral_rate']) . "% = " . number_format($referral_commission['commission']);
                    //                    $orderTemp[$i]['total_commission']       = number_format($orderTemp[$i]['commission']) . " + " . number_format($referral_commission['commission']) . " = " . number_format($orderTemp[$i]['commission'] + $referral_commission['commission']);
                    break;
                case 3:

                    $proCat = $orderById[$orderTemp[$i]['customer_id']]['product_category'];
                    $proCatEx = explode(',', $proCat);

                    if (count($proCatEx) != 1) {
                        $total_commission = 0;
                        $total_rate = 0;
                        foreach ($proCatEx as $categoryId) {
                            $totalSale = $this->totalSalesForCustomerByCategory($orderTemp[$i]['customer_id'], $categoryId, $from, $to)['total_sales'] ?? 0;
                            $cat = Category::find($categoryId);
                            if (!empty($cat)) {
                                $commission_setting = json_decode($cat->data, true);
                            }
                            $total_commission += $this->getCommission($totalSale, $commission_setting)['commission'];
                            $total_rate += $this->getCommission($totalSale, $commission_setting)['rate'];
                        }
                    } else {
                        $cat = Category::find($proCat);
                        if (!empty($cat)) {
                            $commission_setting = json_decode($cat->data, true);
                        }
                        $total_commission = $this->getCommission($orderTemp[$i]['total_group_sales'], $commission_setting)['commission'];
                        $total_rate = $this->getCommission($orderTemp[$i]['total_group_sales'], $commission_setting)['rate'];
                    }

                    $orderTemp[$i]['commission'] = $total_commission;
                    $orderTemp[$i]['rate'] = $total_rate;
                    break;
            }
        }
        return $orderTemp;
    }

    public function totalSalesForCustomerByCategory($customerId, $productCategory, $from, $to)
    {
        $orders = Order::model()->select([
            DB::raw('sum(od.qty * od.price) - sum(od.qty * od.discount) as total_sales'),
        ])
            ->join('users as u', 'u.id', '=', 'orders.customer_id')
            ->leftJoin('order_details as od', 'od.order_id', '=', 'orders.id')
            ->where('orders.order_type', ORDER_TYPE_AGENCY)
            ->where('orders.status', ORDER_STATUS_COMPLETED)
            ->where('u.company_id', TM::getCurrentCompanyId())
            ->where('u.type', USER_TYPE_CUSTOMER)
            ->where('u.group_code', USER_GROUP_AGENT)
            ->where('u.is_active', '1')
            ->where('customer_id', $customerId)
            ->where('od.product_category', $productCategory);


        if (!empty($from)) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '>=',
                date('Y-m-d', strtotime($from))
            );
        }

        if (!empty($to)) {
            $orders = $orders->where(
                DB::raw('date_format(`orders`.`created_at`, "%Y-%m-%d")'),
                '<=',
                date('Y-m-d', strtotime($to))
            );
        }
        $orders = $orders->first()->toArray();

        return $orders;
    }

    private function getCommission($money, $setting)
    {
        $rate = 0;
        foreach ($setting as $item) {
            if ($money >= $item['key']) {
                $rate = $item['value'];
            }
        }

        $commission = $rate * $money / 100;

        return ['rate' => $rate, 'commission' => $commission];
    }

    private function getLevel1Commission($order, $user, $orderById, $setting, $from = null, $to = null)
    {
        $level2Commission = 0;
        $level1Sales = 0;
        $level1Category = $order['product_category'];
        $level1CategoryEx = explode(',', $level1Category);
        $level1TotalPT = 0;
        $total = [];
        foreach ($level1CategoryEx as $keyC => $categoryId) {
            $cat = Category::model()->where('id',$categoryId)->select('data')->first();
            if (!empty($cat)) {
                $setting = json_decode($cat->data, true);
            }
            $level1Sales += $this->totalSalesForCustomerByCategory($order['customer_id'], $categoryId, $from, $to)['total_sales'] ?? 0;

            if (!empty($order['user_level2_ids'])) {
                $children = explode(',', $order['user_level2_ids']);
                foreach ($children as $key => $child) {
                    if (empty($user[$child])) {
                        continue;
                    }
                    $total2Sale = $this->totalSalesForCustomerByCategory($orderById[$child], $categoryId, $from, $to)['total_sales'] ?? 0;
                    $level1Sales += $total2Sale;
                    $level2Sales = $total2Sale;
                    $level3Commission = 0;
                    if (!empty($orderById[$child]['user_level3_ids'])) {
                        $children3 = explode(',', $orderById[$child]['user_level3_ids']);
                        foreach ($children3 as $child3) {
                            if (empty($user[$child3]) || empty($orderById[$child3])) {
                                continue;
                            }
                            $total3Sale = $this->totalSalesForCustomerByCategory($orderById[$child3], $categoryId, $from, $to)['total_sales'] ?? 0;
                            $level1Sales += $total3Sale;
                            $level2Sales += $total3Sale;
                            $level3Commission += $this->getCommission($total3Sale, $setting)['commission'];
                        }
                    }
                    $level2Commission += $this->getCommission($level2Sales, $setting)['commission'];
                }
            }
            $total_commission = $this->getCommission($level1Sales, $setting)['commission'];

            $level1TotalPT += $total_commission;
            $total[$categoryId] = $level1Sales;
            $level1Sales = 0;
        }
        return ['rate' => 0, 'commission' => $level1TotalPT - $level2Commission];
    }

    private function getReferralCommission($personSaleMoney, $groupSaleMoney, $branch, $setting)
    {
        $rate = 0;
        foreach ($setting as $item) {
            if ($personSaleMoney >= $item['key']) {
                $max = 0;
                foreach ($item['branches'] as $br) {
                    if ($branch >= $br['key'] && $br['key'] > $max) {
                        $max = $br['key'];
                        $rate = $br['value'];
                    }
                }
            }
        }

        $commission = $rate * $groupSaleMoney / 100;

        return ['rate' => $rate, 'commission' => $commission];
    }

    private function object_to_array($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = object_to_array($value);
            }
            return $result;
        }
        return $data;
    }


    public function calculateForTreeNonCate($orders, $commission_setting, $referral_setting)
    {
        $settingValue = $referral_setting['value'];
        $referral_setting = $referral_setting['data'];

        $user = User::model()->select([
            'id',
            'level_number',
            'user_level1_id',
            'user_level2_ids',
            'user_level3_ids'
        ])
            ->whereIn('id', array_column($orders, 'customer_id'))
            ->get()->pluck(null, 'id')->toArray();

        $orderById = [];
        foreach ($orders as $order) {
            $orderById[$order['customer_id']] = $order;
        }

        foreach ($orders as $i => $order) {
            $orders[$i]['total_group_sales'] = $order['total_sales'];
            $orders[$i]['total_referral_sales'] = 0;
            switch ($order['customer_level']) {
                case 1:
                    $branch = 0;
                    $orderLevel2TotalSale = [];
                    if (!empty($order['user_level2_ids'])) {
                        $userLevel2 = explode(",", $order['user_level2_ids']);
                        $orderTmp2s = $orders;
                        foreach ($orderTmp2s as $item2) {
                            if (in_array($item2['customer_id'], $userLevel2)) {
                                $orders[$i]['total_group_sales'] += $item2['total_sales'];
                                $orders[$i]['total_referral_sales'] += $item2['total_sales'];
                                $orderLevel2TotalSale[$item2['customer_id']] = $item2['total_sales'];
                            }
                        }
                    }

                    $userLevelTotalSale = [];
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                $orders[$i]['total_referral_sales'] += $item3['total_sales'];
                                $userLevelTotalSale[$item3['user_level2_ids']] = $userLevelTotalSale[$item3['user_level2_ids']] ?? 0;
                                $userLevelTotalSale[$item3['user_level2_ids']] += $item3['total_sales'];
                            }
                        }
                    }

                    foreach ($orderLevel2TotalSale as $level2Id => $total) {
                        if (($userLevelTotalSale[$level2Id] ?? 0) >= $settingValue) {
                            $orders[$i]['total_referral_sales'] -= ($userLevelTotalSale[$level2Id] ?? 0);
                        }

                        $level2TotalSale = $total + ($userLevelTotalSale[$level2Id] ?? 0);
                        if ($level2TotalSale >= $settingValue) {
                            $branch++;
                        } else {
                            $orders[$i]['total_group_sales'] -= $level2TotalSale;
                        }
                    }

                    // Commission
                    $total_commission = $this->getLevel1CommissionNonCate($orders[$i], $user, $orderById, $commission_setting);
                    $orders[$i]['commission'] = $total_commission['commission'];
                    $orders[$i]['rate'] = $total_commission['rate'];

                    // Referral Commission
                    $referral_commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orders[$i]['total_referral_sales'],
                        $branch,
                        $referral_setting
                    );
                    $orders[$i]['referral_commission'] = $referral_commission['commission'];
                    $orders[$i]['referral_rate'] = $referral_commission['rate'];

                    // Format
                    $total_group_commission = $this->getCommission(
                        $orders[$i]['total_group_sales'],
                        $commission_setting
                    );
                    $total_member_commission = $total_group_commission['commission'] - $orders[$i]['commission'];
                    $orders[$i]['commission_group_rate'] = number_format($orders[$i]['total_group_sales']) . " x " . $orders[$i]['rate'] . "% = " . number_format($total_group_commission['commission']);
                    $orders[$i]['commission_person_rate'] = number_format($total_group_commission['commission']) . " - " . number_format($total_member_commission) . " = " . number_format($orders[$i]['commission']);
                    $orders[$i]['commission_referral'] = number_format($orders[$i]['total_referral_sales']) . " x " . number_format($orders[$i]['referral_rate']) . "% = " . number_format($referral_commission['commission']);
                    $orders[$i]['total_commission'] = number_format($orders[$i]['commission']) . " + " . number_format($referral_commission['commission']) . " = " . number_format($orders[$i]['commission'] + $referral_commission['commission']);
                    break;
                case 2:
                    $branch = 0;
                    $child_commission = 0;
                    if (!empty($order['user_level3_ids'])) {
                        $userLevel3 = explode(",", $order['user_level3_ids']);
                        $orderTmp3s = $orders;
                        foreach ($orderTmp3s as $item3) {
                            if (in_array($item3['customer_id'], $userLevel3)) {
                                if ($item3['total_sales'] >= $settingValue) {
                                    $orders[$i]['total_referral_sales'] += $item3['total_sales'];
                                    $branch++;
                                }
                                $orders[$i]['total_group_sales'] += $item3['total_sales'];
                                $child_commission += $this->getCommission(
                                    $item3['total_sales'],
                                    $commission_setting
                                )['commission'];
                            }
                        }
                    }

                    // Commission
                    $total_commission = $this->getCommission($orders[$i]['total_group_sales'], $commission_setting);
                    $orders[$i]['commission'] = $total_commission['commission'] - $child_commission;
                    $orders[$i]['rate'] = $total_commission['rate'];

                    // Referral Commission
                    $referral_commission = $this->getReferralCommission(
                        $order['total_sales'],
                        $orders[$i]['total_referral_sales'],
                        $branch,
                        $referral_setting
                    );
                    $orders[$i]['referral_commission'] = $referral_commission['commission'];
                    $orders[$i]['referral_rate'] = $referral_commission['rate'];

                    // Format
                    $total_group_commission = $this->getCommission(
                        $orders[$i]['total_group_sales'],
                        $commission_setting
                    );
                    $total_member_commission = $total_group_commission['commission'] - $orders[$i]['commission'];
                    $orders[$i]['commission_group_rate'] = number_format($orders[$i]['total_group_sales']) . " x " . $orders[$i]['rate'] . "% = " . number_format($total_group_commission['commission']);
                    $orders[$i]['commission_person_rate'] = number_format($total_group_commission['commission']) . " - " . number_format($total_member_commission) . " = " . number_format($orders[$i]['commission']);
                    $orders[$i]['commission_referral'] = empty($orders[$i]['referral_rate']) ? '' : number_format($orders[$i]['total_referral_sales']) . " x " . number_format($orders[$i]['referral_rate']) . "% = " . number_format($referral_commission['commission']);
                    $orders[$i]['total_commission'] = number_format($orders[$i]['commission']) . " + " . number_format($referral_commission['commission']) . " = " . number_format($orders[$i]['commission'] + $referral_commission['commission']);
                    break;
                case 3:
                    $total_commission = $this->getCommission($orders[$i]['total_group_sales'], $commission_setting);
                    $orders[$i]['commission'] = $total_commission['commission'];
                    $orders[$i]['rate'] = $total_commission['rate'];
                    break;
            }
        }

        return $orders;
    }

    private function getLevel1CommissionNonCate($order, $user, $orderById, $setting)
    {
        $level2Commission = 0;
        $level1Sales = $order['total_sales'];
        if (!empty($order['user_level2_ids'])) {
            $children = explode(',', $order['user_level2_ids']);
            foreach ($children as $child) {
                if (empty($user[$child])) {
                    continue;
                }
                $level1Sales += $orderById[$child]['total_sales'];
                $level2Sales = $orderById[$child]['total_sales'];

                $level3Commission = 0;

                if (!empty($orderById[$child]['user_level3_ids'])) {
                    $children3 = explode(',', $orderById[$child]['user_level3_ids']);
                    foreach ($children3 as $child3) {
                        if (empty($user[$child3]) || empty($orderById[$child3])) {
                            continue;
                        }
                        $level1Sales += $orderById[$child3]['total_sales'];
                        $level2Sales += $orderById[$child3]['total_sales'];
                        $level3Commission += $this->getCommission(
                            $orderById[$child3]['total_sales'],
                            $setting
                        )['commission'];
                    }
                }

                $level2Commission += $this->getCommission($level2Sales, $setting)['commission'];
            }
        }

        $commission = $this->getCommission($level1Sales, $setting);

        return ['rate' => $commission['rate'], 'commission' => ($commission['commission'] - $level2Commission)];
    }
    public function updateMany($id){
        if (TM::getCurrentRole() != USER_ROLE_ADMIN && TM::getCurrentRole() != USER_ROLE_MANAGER && TM::getCurrentRole() != USER_ROLE_SUPERADMIN && TM::getCurrentRole() !=USER_ROLE_MONITOR && TM::getCurrentRole() !=USER_ROLE_SELLER && TM::getCurrentRole() !=USER_ROLE_LEADER) {
            $myUserType = TM::getMyUserType();
            if ($myUserType != USER_TYPE_CUSTOMER) {
                throw new \Exception(Message::get("V003", "ID Customer: #" . TM::getCurrentUserId()));
            }
        }
        $order = Order::model()->where('id',$id)->first();
        if($order->status == ORDER_STATUS_NEW){
                $order->status_crm = ORDER_STATUS_CRM_ADAPPROVED;
        }
        if($order->status == ORDER_STATUS_SHIPPED) {
            $order->status = ORDER_STATUS_COMPLETED;
        }
        $order->save();
        return $order;
    }
}
