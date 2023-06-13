<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 08:57 PM
 */

namespace App\V1\Controllers;


use App\Price;
use App\Exports\PriceExport;
use App\PriceDetail;
use App\UserStore;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\PriceDetailModel;
use App\V1\Models\PriceModel;
use App\V1\Transformers\Price\PriceDetailTransformer;
use App\V1\Transformers\Price\PriceTransformer;
use App\V1\Validators\PriceCreateValidator;
use App\V1\Validators\PriceDetailCreateValidator;
use App\V1\Validators\PriceUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PriceController extends BaseController
{

    protected $model;
    protected $priceDetailModel;

    /**
     * PriceController constructor.
     *
     * @param PriceModel $model
     */
    public function __construct()
    {
        $this->model            = new  PriceModel();
        $this->priceDetailModel = new PriceDetailModel();
    }

    /**
     * @param Request $request
     * @param PriceTransformer $priceTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, PriceTransformer $priceTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $price = $this->model->search($input, [], $limit);
        return $this->response->paginator($price, $priceTransformer);
    }

    /**
     * @param $id
     * @param PriceTransformer $priceTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, PriceTransformer $priceTransformer)
    {
        $price = Price::find($id);
        if (empty($price)) {
            return ['data' => null];
        }
        return $this->response->item($price, $priceTransformer);
    }

    /**
     * @param Request $request
     * @param PriceCreateValidator $priceCreateValidator
     *
     * @return array|void
     */
    public function create(Request $request, PriceCreateValidator $priceCreateValidator)
    {
        $input = $request->all();
        $priceCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $priceCreateValidator->validate($input);
        $orderCheck = Price::model()->where('company_id', TM::getCurrentCompanyId())->where('order', "{$input['order']}")->first();
        if (!empty($orderCheck)) {
            return $this->response->errorBadRequest(Message::get("unique", "Số thứ tự: #{$input['order']}"));
        }
        if (!empty($input['duplicated_from'])) {
            if (!isset($input['dup_type'])) {
                return $this->response->errorBadRequest(Message::get("V001", Message::get("type")));
            }
//            if (!isset($input['value'])) {
//                return $this->response->errorBadRequest(Message::get("V001", Message::get("value")));
//            }
        }
        try {
            DB::beginTransaction();
            $price = $this->model->upsert($input);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("prices.create-success", $price->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param PriceUpdateValidator $priceUpdateValidator
     *
     * @return array|void
     */
    public function update($id, Request $request, PriceUpdateValidator $priceUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $priceUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $priceUpdateValidator->validate($input);

        $orderCheck = Price::model()->where('company_id', TM::getCurrentCompanyId())->where('order', "{$input['order']}")->first();
        if (!empty($orderCheck) && $orderCheck->id != $id) {
            return $this->response->errorBadRequest(Message::get("unique", "Số thứ tự: #{$input['order']}"));
        }

        try {
            DB::beginTransaction();
            $price = $this->model->upsert($input);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("prices.update-success", $price->name)];
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
            $price = Price::find($id);
            if (empty($price)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $price->delete();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("prices.delete-success", $price->name)];
    }
################################ Price Detail ####################################

    /**
     * @param $id
     * @param Request $request
     * @param PriceDetailCreateValidator $validator
     * @return string[]|void
     */
    public function createPriceDetail($id, Request $request, PriceDetailCreateValidator $validator)
    {
        $input = $request->all();
        $price = Price::find($id);
        if (empty($price)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }
        $input['status']   = $price->status ?? null;
        $input['from']     = $price->from ?? null;
        $input['to']       = $price->to ?? null;
        $input['price_id'] = $id ?? null;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $this->priceDetailModel->create($input);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => "Chi tiết cho bảng giá " . Message::get("R001", $price->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param PriceDetailTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function viewPriceDetail($id, Request $request, PriceDetailTransformer $transformer)
    {
        $input      = $request->all();
        $company_id = UserStore::where('store_id', $input['store_id'])->first();
        $price      = Price::model()->where('id', $id)->where('company_id', $company_id->company_id)->first();
        if (empty($price)) {
            return ['data' => null];
        }
        $result = $price->details()->whereHas('product', function ($q) use ($input) {
            if (!empty($input['product_code'])) {
                $q->where('code', 'like', "%{$input['product_code']}%");
            }
            if (!empty($input['product_name'])) {
                $q->where('name', 'like', "%{$input['product_name']}%");
            }
        })->paginate($request->get('limit', 20));
        return $this->response->paginator($result, $transformer);
    }

    /**
     * @param $id
     * @param PriceDetailTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function viewPriceDetails($id, PriceDetailTransformer $transformer)
    {
        $priceDetail = PriceDetail::findOrFail($id);
        return $this->response->item($priceDetail, $transformer);
    }

    public function createPriceDetailByCurrentPrice($idPriceNew, $idPriceOld, Request $request)
    {
        $priceNew = Price::findOrFail($idPriceNew);
        $priceOld = Price::findOrFail($idPriceOld);
        $priceOld->load('details');
        $priceOldDetails     = $priceOld->details;
        $reduction           = $request->get('reduction', 0);
        $data                = [];
        $compareWithPriceOld = $request->get('percent', 0);
        if (!empty($priceOldDetails)) {
            foreach ($priceOldDetails as $detail) {
                $data[] = [
                    'price_id'   => $idPriceNew,
                    'product_id' => $detail->product_id,
                    'from'       => $detail->from,
                    'to'         => $detail->to,
                    'price_ex'   => round($detail->price + (($reduction == 1 ? -1 : 1) * ($detail->price * $compareWithPriceOld / 100)), 0),
                    'status'     => $detail->status,
                    //                    'product_variant_id'     => $detail->product_variant_id,
                ];
            }
        }
        $data['details'] = $data;
        try {
            DB::beginTransaction();
            $this->priceDetailModel->create($data);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => "Chi tiết cho bảng giá " . Message::get("R001", $priceNew->name)];
    }

    public function updatePriceDetails($id, Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $priceDetail         = PriceDetail::findOrFail($id);
            $priceDetail->price  = Arr::get($input, 'price', 0);
            $priceDetail->status = Arr::get($input, 'status', null);
            $priceDetail->save();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", "ID #$id")];
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function deleteDetail($id)
    {
        try {
            DB::beginTransaction();
            $priceDetail = PriceDetail::find($id);
            if (empty($priceDetail)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $priceDetail->delete();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", "ID #$id")];
    }

    public function priceExportExcel()
    {
        //ob_end_clean();
        $prices = Price::model()->get();
        //ob_start(); // and this
        return Excel::download(new PriceExport($prices), 'list_price.xlsx');
    }
}