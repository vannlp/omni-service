<?php
/**
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 3:08 PM
 */

namespace App\V1\Models;


use App\Price;
use App\Product;
use App\SalePrice;
use App\SalePriceDetail;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Unit;
use App\UserCustomerGroup;
use App\UserGroup;
use Illuminate\Support\Facades\DB;

class SalePriceModel extends AbstractModel
{
    public function __construct(SalePrice $model = null)
    {
        parent::__construct($model);
    }

    //set price
    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $products = new ProductModel();
        $productsTable = $products->getTable();
        $prices = new PriceModel();
        $pricesTable = $prices->getTable();
        $salePrice = new SalePriceModel();
        $salePriceTable = $salePrice->getTable();
        $query->where('company_id', TM::getCurrentCompanyId());
        if (!empty($input['product_code'])) {
            $query->join($productsTable, $productsTable . '.id', '=', $salePriceTable . '.product_id')
                ->join($pricesTable, $pricesTable . '.id', '=', $salePriceTable . '.price_id')
                ->where($productsTable . '.code', 'like', "%{$input['product_code']}%");
        }
        if (!empty($input['product_name'])) {
            $query->join($productsTable, $productsTable . '.id', '=', $salePriceTable . '.product_id')
                ->join($pricesTable, $pricesTable . '.id', '=', $salePriceTable . '.price_id')
                ->where($productsTable . '.name', 'like', "%{$input['product_name']}%");
        }

        if (!empty($input['customer_group_ids'])) {
            $customer_group_ids = explode(',', $input['customer_group_ids']);
            $query = $query->where(function ($q) use ($customer_group_ids) {
                foreach ($customer_group_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',customer_group_ids,',')"), 'like', "%,$item,%");
                }
            });
        }
        if (!empty($input['price_id'])) {
            $query->where('price_id', 'like', "%{$input['price_id']}%");
        }
        if (!empty($input['cs_number'])) {
            $query->where('cs_number', 'like', "%{$input['cs_number']}%");
        }
        if (!empty($input['order_date'])) {
            $date = date("Y-m-d", strtotime($input['order_date']));
            $query = $query->where('sale_prices.from', '<=', $date)
                ->where('sale_prices.to', '>=', $date)
                ->groupBy([
                    'sale_prices.product_id',
                    'sale_prices.unit_id',
                    'sale_prices.price_id',
                ])
                ->orderBy('sale_prices.product_id')
                ->orderBy('sale_prices.unit_id')
                ->orderBy('sale_prices.price_id')
                ->orderBy('sale_prices.updated_at', 'desc')
                ->select(DB::raw("max(sale_prices.id) as id"))->get()->pluck("id")->toArray();

            $query = $this->model->whereIn('id', $query);

        } else {
            $query->select($salePriceTable . '.*');
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

    public function upsert($input)
    {
        $price = Price::model()->where('id', $input['price_id'])->first();
        $unit = Unit::model()->where('id', $input['unit_id'])->first();
        $product = Product::model()->where('id', $input['product_id'])->first();
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if (!empty($input['customer_group_ids'])) {
                $cusGroupId = (implode(",", $input['customer_group_ids']));
            }
            if ($id) {
                $salePrice = SalePrice::find($id);

                if (empty($salePrice)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $salePrice->product_id = array_get($input, 'product_id', $salePrice->product_id);
                $salePrice->product_code = $product->code;
                $salePrice->product_name = $product->name;
                $salePrice->unit_id = array_get($input, 'unit_id', $salePrice->unit_id);
                $salePrice->unit_code = $unit->code;
                $salePrice->unit_name = $unit->name;
                $salePrice->price = array_get($input, 'price', $salePrice->price);
                $salePrice->customer_group_ids = !empty($cusGroupId) ? $cusGroupId : null;
                $salePrice->discount = array_get($input, 'discount', 0);
                $salePrice->price_id = $price->id;
                $salePrice->price_code = $price->code;
                $salePrice->price_name = $price->name;
                $salePrice->description = array_get($input, 'description', $salePrice->description);
                $salePrice->cs_number = array_get($input, 'cs_number', $salePrice->cs_number);
                $salePrice->seed_level = array_get($input, 'seed_level', $salePrice->seed_level);
                $salePrice->packing_standard = array_get($input, 'packing_standard', $salePrice->packing_standard);
                $salePrice->from = date("Y-m-d", strtotime(array_get($input, 'from', $salePrice->from)));
                $salePrice->to = date("Y-m-d", strtotime(array_get($input, 'to', $salePrice->to)));
                $salePrice->is_active = array_get($input, 'is_active', 1);
                $salePrice->company_id = array_get($input, 'company_id', $salePrice->company_id);
                $salePrice->updated_at = date("Y-m-d H:i:s", time());
                $salePrice->updated_by = TM::getCurrentUserId();
                $salePrice->save();
            } else {
                $product = Product::find($input['product_id'])->first();
                $unit = Unit::find($input['unit_id'])->first();
                $price = Price::find($input['price_id'])->first();
                $param = [
                    'product_id'         => array_get($input, 'product_id'),
                    'product_code'       => $product->code,
                    'product_name'       => $product->name,
                    'unit_id'            => array_get($input, 'unit_id'),
                    'unit_code'          => $unit->code,
                    'unit_name'          => $unit->name,
                    'price'              => array_get($input, 'price'),
                    'cs_number'          => array_get($input, 'cs_number'),
                    'seed_level'         => array_get($input, 'seed_level', null),
                    'packing_standard'   => array_get($input, 'packing_standard', null),
                    'customer_group_ids' => !empty($cusGroupId) ? $cusGroupId : null,
                    'discount'           => array_get($input, 'discount', null),
                    'price_id'           => array_get($input, 'price_id', null),
                    'price_code'         => $price->code,
                    'price_name'         => $price->name,
                    'company_id'         => TM::getCurrentCompanyId(),
                    'description'        => array_get($input, 'description', null),
                    'is_active'          => array_get($input, 'is_active', 1),
                    'from'               => date("Y-m-d", strtotime(array_get($input, 'from'))),
                    'to'                 => date("Y-m-d", strtotime(array_get($input, 'to'))),
                ];
                $salePrice = $this->create($param);
            }
            if (!empty($input['customer_group_ids'])) {
                $details = $input['customer_group_ids'];
                // Create|Update Sale Price Detail
                $allSalePriceDetail = SalePriceDetail::model()->where('sale_price_id',
                    $salePrice->id)->get()->toArray();
                $allSalePriceDetail = array_pluck($allSalePriceDetail, 'id', 'customer_group_ids');
                $allSalePriceDetailDelete = $allSalePriceDetail;
                foreach ($details as $detail) {
                    if (empty($allSalePriceDetail[$detail])) {

                        // Create Detail
                        $salePriceDetail = new SalePriceDetail();
                        $salePriceDetail->create([
                            'sale_price_id'     => $salePrice->id,
                            'customer_group_ids' => $detail,
                            'company_id'         => TM::getCurrentCompanyId(),
                            'is_active'          => 1,
                        ]);
                        continue;
                    }

                    unset($allSalePriceDetailDelete[$detail]);

                    $salePriceDetail = SalePriceDetail::find($allSalePriceDetail[$detail]);
                    $salePriceDetail->sale_price_id = $salePrice->id;
                    $salePriceDetail->customer_group_ids = $detail;
                    $salePriceDetail->company_id = TM::getCurrentCompanyId();
                    $salePriceDetail->updated_at = date('Y-m-d H:i:s', time());
                    $salePriceDetail->updated_by = TM::getCurrentUserId();
                    $salePriceDetail->save();
                }
                // Delete Sale Price Detail
                SalePriceDetail::model()->whereIn('id', array_values($allSalePriceDetailDelete))->delete();
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $salePrice;
    }

    public function getListSalePrice()
    {
        $query = DB::table('sale_prices')->get();
        return $query;
    }

    public static function getNameProduct($id)
    {
        $query = DB::table('products')
            ->select('name')
            ->where('id', '=', $id)
            ->first();
        return $query;
    }
}