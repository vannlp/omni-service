<?php
/**
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 3:06 PM
 */

namespace App\V1\Controllers;


use App\SalePrice;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\SalePriceModel;
use App\V1\Transformers\SalePrice\SalePriceTransformer;
use App\V1\Validators\SalePriceCreateValidator;
use App\V1\Validators\SalePriceUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalePriceController extends BaseController
{
    /**
     * @var SalePriceModel
     */
    protected $model;

    public function __construct(SalePriceModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param SalePriceTransformer $salePriceTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, SalePriceTransformer $salePriceTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $salePrice = $this->model->search($input, [], $limit);
        return $this->response->paginator($salePrice, $salePriceTransformer);
    }

    /**
     * @param $id
     * @param SalePriceTransformer $salePriceTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, SalePriceTransformer $salePriceTransformer)
    {
        $salePrice = SalePrice::find($id);
        if (empty($salePrice)) {
            return ['data' => ''];
        }
        return $this->response->item($salePrice, $salePriceTransformer);
    }

    /**
     * @param Request $request
     * @param SalePriceCreateValidator $salePriceCreateValidator
     * @return array|mixed|null|void
     */
    public function create(Request $request, SalePriceCreateValidator $salePriceCreateValidator)
    {
        $input = $request->all();
        $salePriceCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $salePrice = SalePrice::model()
                ->where('product_id', $input['product_id'])
                ->where('unit_id', $input['unit_id'])
                ->where('price_id', $input['price_id']);
            if (!empty($input['customer_group_ids'])) {
                $salePrice = $salePrice->where('customer_group_ids', implode(',', $input['customer_group_ids']));
            }
            if (!empty($input['from'] && !empty($input['to']))) {
                $salePrice = $salePrice->whereDate('from', date('Y-m-d', strtotime($input['from'])))
                    ->whereDate('from', date('Y-m-d', strtotime($input['to'])));
            }
            $salePrice = $salePrice->first();

            if (!empty($salePrice)) {
                return $this->response->errorBadRequest(Message::get("sale_prices.exist-fk"));
            }
            $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("sale_prices.create-success", $input['product_id'])];
    }

    /**
     * @param $id
     * @param Request $request
     * @param SalePriceUpdateValidator $salePriceUpdateValidator
     * @return array|mixed|null|void
     */
    public function update($id, Request $request, SalePriceUpdateValidator $salePriceUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $salePriceUpdateValidator->validate($input);

        try {

            $salePrice = SalePrice::model()
                ->where('product_id', $input['product_id'])
                ->where('unit_id', $input['unit_id'])
                ->where('price_id', $input['price_id']);

            if (!empty($input['customer_group_ids'])) {
                $salePrice = $salePrice->where('customer_group_ids', implode(',', $input['customer_group_ids']));
            }
            if (!empty($input['from'] && !empty($input['to']))) {
                $salePrice = $salePrice->whereDate('from', date('Y-m-d', strtotime($input['from'])))
                    ->whereDate('from', date('Y-m-d', strtotime($input['to'])));
            }
            $salePrice = $salePrice->first();

            if (!empty($salePrice) && $salePrice->id != $id) {
                return $this->response->errorBadRequest(Message::get("sale_prices.exist-fk"));
            }

            $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("sale_prices.update-success", $id)];
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $salePrice = SalePrice::find($id);
            $product_name = $this->model->getNameProduct($salePrice->product_id);
            if (empty($salePrice)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $salePrice->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("sale_prices.delete-success", $product_name->name)];
    }

//    public function exportSalePrice(Request $request)
//    {
//        $input = $request->all();
//        try {
//            $date = date('YmdHis', time());
//
//            $salePrices = $this->model->search($input, ['product', 'units', 'groups', 'types', 'product.cate']);
//            $dataSaleProduct = [
//                [
//                    'PHÂN NHÓM NVG',
//                    'MÃ HÀNG',
//                    'TÊN HÀNG',
//                    'ĐỐI TƯỢNG',
//                    'ĐVT',
//                    'GIÁ',
//                    'HIỆU LỰC TỪ',
//                    'HIỆU LỰC ĐẾN',
//                    'NGÀY',
//                    'CHIẾT KHẤU',
//                    'SỐ CHÍNH SÁCH',
//                    'GHI CHÚ'
//                ]
//            ];
//            foreach ($salePrices as $salePrice) {
//
//                $fromDate = array_get($salePrice, "types.from", null);
//                $toDate = array_get($salePrice, "types.to", null);
//                $dataSaleProduct[] = [
//                    'category'     => array_get($salePrice, "product.cate.name", null),
//                    'product_code' => array_get($salePrice, "product.code", null),
//                    'product_name' => array_get($salePrice, "product.name", null),
//                    'groups'       => array_get($salePrice, "groups.name", null),
//                    'unit'         => array_get($salePrice, "units.name", null),
//                    'price'        => $salePrice['price'],
//                    'from_date'    => date('d/m/Y H:i:s', strtotime($fromDate)),
//                    'to_date'      => date('d/m/Y H:i:s', strtotime($toDate)),
//                    'date'         => date('d/m/Y H:i:s', strtotime($salePrice['created_at'])),
//                    'discount'     => $salePrice['discount'],
//                    'cs_number'    => $salePrice['cs_number'],
//                    'description'  => $salePrice['description']
//                ];
//            }
//
//            $this->ExcelExport("SalePrice_$date", storage_path('Export') . "SaleProduct/", $dataSaleProduct);
//        } catch (\Exception $ex) {
//            DB::rollBack();
//            $response = Vinaseed_Error::handle($ex);
//            return $this->response->errorBadRequest($response["message"]);
//        }
//    }
}