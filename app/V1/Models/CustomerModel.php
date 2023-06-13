<?php
/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 07:51 PM
 */

namespace App\V1\Models;


use App\Customer;
use App\CustomerProfile;
use App\CustomerType;
use App\Order;
use App\SSC;
use App\Supports\Message;
use App\UnitConvert;
use Illuminate\Support\Facades\DB;


class CustomerModel extends AbstractModel
{
    public function __construct(Customer $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $phone = "";
        if (!empty($input['phone'])) {
            $phone = str_replace(" ", "", $input['phone']);
            $phone = preg_replace('/\D/', '', $phone);
        }

        $y = date('Y', time());
        $m = date("m", time());
        $d = date("d", time());
        $dir = !empty($input['avatar']) ? "$y/$m/$d" : null;
        $file_name = empty($dir) ? null : "avatar_{$input['phone']}";
        if ($file_name) {
            $avatars = explode("base64,", $input['avatar']);
            $input['avatar'] = $avatars[1];
            if (!empty($file_name) && !is_image($avatars[1])) {
                return $this->response->errorBadRequest(Message::get("V002", "Avatar"));
            }
        }

        DB::beginTransaction();

        $id = !empty($input['id']) ? $input['id'] : 0;

        if ($id) {
            // Update Customer
            $param['id'] = $id;
            $customer = Customer::find($id);
            $customer->code = array_get($input, 'code', $customer->code);
            $customer->name = array_get($input, 'name', $customer->name);
            $customer->card_name = strtoupper(trim(array_get($input, 'card_name', $customer->card_name)));
            $customer->sscid = array_get($input, 'sscid', $customer->sscid);
            $customer->email = array_get($input, 'email', $customer->email);
            $customer->password = !empty($input['password']) ? password_hash($input['password'],
                PASSWORD_BCRYPT) : $customer->password;
            $customer->phone = !empty($phone) ? $phone : $customer->phone;
            $customer->note = array_get($input, 'note', $customer->note);
            $customer->is_seller = array_get($input, 'is_seller', $customer->is_seller);
            $customer->group_id = array_get($input, 'group_id', $customer->group_id);
            $customer->type_id = array_get($input, 'type_id', $customer->type_id);
            $customer->is_agency = array_get($input, 'is_agency', $customer->is_agency);
            $customer->updated_at = date("Y-m-d H:i:s", time());
            $customer->updated_by = TM::getCurrentUserId();
            $customer->save();
        } else {
            $param = [
                'phone'        => $phone,
                'code'         => $input['code'],
                'name'         => $input['name'],
                'card_name'    => strtoupper(trim(array_get($input, 'card_name', $input['name']))),
                'sscid'        => $input['sscid'],
                'group_id'     => array_get($input, 'group_id'),
                'type_id'      => array_get($input, 'type_id'),
                'email'        => array_get($input, 'email'),
                'verify_code'  => mt_rand(100000, 999999),
                'expired_code' => date('Y-m-d H:i:s', strtotime("+5 minutes")),
                'is_active'    => array_get($input, 'is_active', 1),
                'is_agency'    => array_get($input, 'is_agency', 1),
            ];
            $param['password'] = !empty($input['password']) ?
                password_hash($input['password'], PASSWORD_BCRYPT) :
                password_hash(config('constants.customer_password_default'), PASSWORD_BCRYPT);

            // Create Customer
            $customer = $this->create($param);
            if (empty($input['sscid'])) {
                $user = new UserModel;
                $user->upsert($input);
            }
        }

        $profile = CustomerProfile::where(['customer_id' => $customer->id])->first();
        $names = explode(" ", trim($input['name']));
        $first = $names[0];
        unset($names[0]);
        $last = !empty($names) ? implode(" ", $names) : null;

        $prProfile = [
            'email'           => array_get($input, 'email'),
            'is_active'       => 1,
            'first_name'      => $first,
            'last_name'       => $last,
            'short_name'      => $input['name'],
            'full_name'       => $input['name'],
            'branch_name'     => array_get($input, 'branch_name', null),
            'address'         => array_get($input, 'address', null),
            'receipt_address' => array_get($input, 'receipt_address', null),
            'phone'           => array_get($input, 'phone', null),
            'birthday'        => empty($input['birthday']) ? null : $input['birthday'],
            'gender'           => array_get($input, 'gender', "O"),
            'avatar'          => $file_name ? $dir . "/" . $file_name . ".jpg" : null,
            'account_number'  => array_get($input, 'account_number'),
            'tax_number'      => array_get($input, 'tax_number'),
            'bank_type'       => array_get($input, 'bank_type'),
            'spokesman'       => array_get($input, 'spokesman'),
            'id_number'       => array_get($input, 'id_number', 0),
            'customer_id'     => $customer->id,
        ];

        // Create Profile
        $customerProfileMode = new CustomerProfileModel();
        if (empty($profile)) {
            $customerProfileMode->create($prProfile);
        } else {
            $prProfile['id'] = $profile->id;
            $customerProfileMode->update($prProfile);
        }

        DB::commit();

        return $customer;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
//        foreach ($input as $column => $value) {
//            if (in_array($column,
//                    ['name', 'code', 'phone', 'group_id',]) && !empty($value)) {
//                if ($column == "group_id" && $value == "empty") {
//                    $query = $query->whereNull('group_id');
//                } else {
//                    $query = $query->where($column, 'like', "%$value%");
//                }
//            }
//        }
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['phone'])) {
            $query->where('phone', 'like', "%{$input['phone']}%");
        }
        if (!empty($input['group_id'])) {
            $customer_group_ids = explode(',', $input['group_id']);
            $query = $query->where(function ($q) use ($customer_group_ids) {
                foreach ($customer_group_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',group_id,',')"), 'like', "%,$item,%");
                }
            });
        }
        if (!empty($input['group_name'])) {
            $query->whereHas('group', function ($q) use ($input) {
                $q->where('name', 'like', "%{$input['group_name']}%");
            });
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

    public function checkCanDelete($customerId)
    {
        $order = Order::model()->where('customer_id', $customerId)->get()->toArray();
        if (empty($order)) {
            return true;
        }

        foreach ($order as $item) {
            if (in_array($item['status'], ['ACCEPTED', 'SHIPPING', 'RECEIVED'])) {
                return false;
            }
        }
        return true;
    }

    public function getPoint($customerId, $groupId = null, $from = null, $to = null)
    {
        /*
         select pd.id as pd_id, p.id as p_id, c.id as customer_id, pd.customer_group_id, c.group_id, od.id, od.product_id, od.shipped_qty, od.qty, o.*
        from orders o
        join order_details od on od.order_id = o.id
        join promotion_details pd on pd.product_id = od.product_id
        join promotions p on p.id = pd.promotion_id
        join customers cus on cus.id = o.customer_id
        left join customers c on c.group_id = pd.customer_group_id

        where o.customer_id = 1158 and o.status="SHIPPED" and o.order_type = 1
            and od.shipped_qty > 0
            and (pd.customer_group_id is null  or c.id = 1158)
            and cus.is_agency = 1
        order by o.id

        SELECT
    `orders`.`id`,
    `pd`.`id` AS `promotion_detail_id`,
    `p`.`id` AS `promotion_id`,
    `c`.`id` AS `customer_id`,
    `pd`.`customer_group_id`,
    `od`.`id` AS `order_detail_id`,
    `od`.`shipped_qty`,
     `od`.`product_id` as `product_id`,
    `rd`.`qty`,
    `orders`.`order_date`,
    `orders`.`status`,
    `orders`.`order_type`,
    `sp`.`unit_id` AS `order_unit_id`,
    `pd`.`product_id` AS `promo_product_id`,
    `pd`.`qty` AS `promo_qty`,
    `pd`.`unit` AS `promo_unit_id`,
    `pd`.`point` AS `promo_point`
FROM
    `orders`
        INNER JOIN
    `order_details` AS `od` ON `od`.`order_id` = `orders`.`id`
        INNER JOIN
    `promotion_details` AS `pd` ON `pd`.`product_id` = `od`.`product_id`
        INNER JOIN
    `promotions` AS `p` ON `p`.`id` = `pd`.`promotion_id`
        INNER JOIN
    `customers` AS `cus` ON `cus`.`id` = `orders`.`customer_id`
        LEFT JOIN
    `customers` AS `c` ON `c`.`group_id` = `pd`.`customer_group_id`
        INNER JOIN
    `sale_price` AS `sp` ON `sp`.`id` = `od`.`item_id`
	join receipt_details rd on `rd`.`order_detail_id` = `od`.`id`

WHERE
    `orders`.`deleted_at` IS NULL
        AND orders.customer_id = 1158
        AND orders.order_type = 1
        AND (pd.customer_group_id IS NULL
        OR c.id = 1158)
        AND cus.is_agency = 1
        AND od.decrement = 0
        AND DATE_FORMAT(orders.order_date, '%Y-%m-%d 23:59:59') >= p.from
        AND DATE_FORMAT(orders.order_date, '%Y-%m-%d 00:00:00') <= p.to
        AND p.type IS NULL
        AND `orders`.`deleted_at` IS NULL
ORDER BY `orders`.`id` ASC

         */

        if ($groupId) {
            $groupId = " and (" .
                "pd.customer_group_id is null or " .
                "pd.customer_group_id = '' or " .
                "concat(',', pd.customer_group_id, ',') like '%,$groupId,%'" .
                ") ";
        }

        if ($from) {
            $from = " and orders.order_date >= '" . (date("Y-m-d", strtotime($from))) . "' ";
        }
        if ($to) {
            $to = " and orders.order_date <= '" . (date("Y-m-d", strtotime($to))) . "' ";
        }


        $result = Order::model()->select([
            'orders.id',
            'od.product_id as product_id',
            'pd.id as promotion_detail_id',
            'p.id as promotion_id',
            'cus.id as customer_id',
            'pd.customer_group_id',
            'od.id as order_detail_id',
            'rd.qty',
            'orders.order_date',
            'orders.status',
            'orders.order_type',
            'sp.unit_id as order_unit_id',
            'pd.product_id as promo_product_id',
            'pd.qty as promo_qty',
            'pd.unit as promo_unit_id',
            'pd.point as promo_point',
        ])
            ->join('order_details as od', 'od.order_id', '=', 'orders.id')
            ->join('promotion_details as pd', 'pd.product_id', '=', 'od.product_id')
            ->join('promotions as p', 'p.id', '=', 'pd.promotion_id')
            ->join('customers as cus', 'cus.id', '=', 'orders.customer_id')
            ->join('sale_prices as sp', 'sp.id', '=', 'od.item_id')
            ->join('receipt_details as rd', 'rd.order_detail_id', '=', 'od.id')
            ->whereRaw("
                od.deleted_at is null and
                pd.deleted_at is null and
                p.deleted_at is null and
                sp.deleted_at is null and
                orders.customer_id = $customerId and orders.order_type = " . ORDER_TYPE_NORMAL . " and
                orders.status in ('" . ORDER_STATUS_APPROVED . "', '" . ORDER_STATUS_SHIPPED . "', '" . ORDER_STATUS_COMPLETED . "')
                and cus.is_agency = 1
                and od.decrement = 0
                and DATE_FORMAT(orders.order_date, '%Y-%m-%d 23:59:59') >= p.from
                and DATE_FORMAT(orders.order_date, '%Y-%m-%d 00:00:00') <= p.to
                and p.type is null
                and rd.order_type = " . ORDER_TYPE_NORMAL . "
                $groupId
                $from
                $to
            ")
            ->orderBy('orders.id')
            ->get()->toArray();

        if (empty($result)) {
            return 0;
        }

        $returnOrders = Order::model()
            ->select([
                'orders.id as id',
                'o.id as ref_id',
                'od.product_id',
                DB::raw('sum(rd.qty) as qty'),
            ])
            ->join('receipt_details as rd', 'rd.order_id', '=', 'orders.id')
            ->join('order_details as od', 'od.id', '=', 'rd.order_detail_id')
            ->join('orders as o', 'o.code', '=', 'orders.ref_order_code')
            ->whereNull('od.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('orders.customer_id', $customerId)
            ->whereIn('orders.status', [ORDER_STATUS_APPROVED, ORDER_STATUS_COMPLETED])
            ->where('orders.order_type', ORDER_TYPE_REFUND)
            ->groupBy('orders.id')
            ->groupBy('od.product_id')
            ->get()->toArray();

        $unitConverts = UnitConvert::model()->get();
        $converts = [];
        foreach ($unitConverts as $unitConvert) {
            $converts[$unitConvert->from_unit_id . "-" . $unitConvert->to_unit_id] = $unitConvert->rate;
        }

        $returns = [];
        foreach ($returnOrders as $order) {
            $returns[$order['ref_id']][$order['product_id']] = $returns[$order['ref_id']][$order['product_id']] ?? 0;
            $returns[$order['ref_id']][$order['product_id']] += $order['qty'];
        }

        $point = 0;
        $totalQty = [];
        $qtyReturned = [];
        foreach ($result as $item) {
            if (empty($converts[$item['order_unit_id'] . "-" . $item['promo_unit_id']])) {
                $rate = 1;
            } else {
                $rate = $converts[$item['order_unit_id'] . "-" . $item['promo_unit_id']];
            }
            $qty = $item['qty'];

            if (!empty($returns[$item['id']]) && !empty($returns[$item['id']][$item['product_id']]) && empty($qtyReturned[$item['id']][$item['product_id']])) {
                $qtyReturned[$item['id']][$item['product_id']] = true;
                $qty -= $returns[$item['id']][$item['product_id']];
            }

            $totalQty[$item['promotion_id'] . "-" . $item['product_id']] = $totalQty[$item['promotion_id'] . "-" . $item['product_id']] ?? 0;
            $totalQty[$item['promotion_id'] . "-" . $item['product_id']] += $qty;

            // Check to get Point
            if ($totalQty[$item['promotion_id'] . "-" . $item['product_id']] >= $item['promo_qty'] / $rate) {

                $total = ($totalQty[$item['promotion_id'] . "-" . $item['product_id']] * $rate) / $item['promo_qty'];
                $qtyGetPoint = floor($total);
                $qtyContinueStore = $totalQty[$item['promotion_id'] . "-" . $item['product_id']] - ($qtyGetPoint / $rate);
                $totalQty[$item['promotion_id'] . "-" . $item['product_id']] = $qtyContinueStore;

                // Get Point
                $point += $qtyGetPoint * $item['promo_point'];
            }
        }

        return $point;
    }

    /**
     * @param $point
     * @return array
     */
    public function getType($point)
    {
        $type = [
            'id'   => null,
            'code' => null,
            'name' => null,
        ];
        if (empty($point) || $point < 0) {
            return $type;
        }

        $customerType = CustomerType::model()->orderBy('point', 'desc')->get();
        foreach ($customerType as $cusType) {
            if ($point >= $cusType->point) {
                $type = [
                    'id'   => $cusType->id,
                    'code' => $cusType->code,
                    'name' => $cusType->name,
                ];
                break;
            }
        }

        return $type;
    }

    /**
     * @param int $isAgency
     * @return array
     */
    public function getAllGroupAndCustomer($isAgency = 1)
    {
        $allCustomers = $this->model->select(['id', 'code', 'name', 'email', 'group_id'])
            ->where('is_agency', $isAgency)->where('is_active', 1)->get()->toArray();
        $customers = !empty($allCustomers) ? ['all' => $allCustomers] : [];
        foreach ($allCustomers as $customer) {
            if (!empty($customer['group_id'])) {
                $customers[$customer['group_id']][] = $customer;
            }
        }

        return $customers;
    }
}
