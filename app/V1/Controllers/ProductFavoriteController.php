<?php


namespace App\V1\Controllers;


use App\Company;
use App\Product;
use App\ProductFavorite;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\ProductFavoriteModel;
use App\V1\Models\ProductModel;
use App\V1\Transformers\Company\CompanyTransformer;
use App\V1\Transformers\Product\ProductTransformer;
use App\V1\Transformers\ProductFavorite\ProductFavoriteTransformer;
use App\V1\Validators\CompanyUpdateValidator;
use App\V1\Validators\ProductFavorite\ProductFavoriteCreateValidator;
use App\V1\Validators\ProductFavorite\ProductFavoriteUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductFavoriteController extends BaseController
{
    /**
     * @var ProductFavoriteModel
     */
    protected $model;
    protected $productModel;

    /**
     * ProductFavoriteController constructor.
     */
    public function __construct()
    {
        $this->model = new ProductFavoriteModel();
        $this->productModel = new ProductModel();
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, ProductTransformer $productTransformer)
    {
        $input = $request->all();
        $limit = Arr::get($input, 'limit', 20);
        $input['user_id'] = TM::getCurrentUserId();

        $result = $this->model->search($input, ['product', 'user'], $limit);
        $productIds = array_pluck($result, 'product_id', null);
        if (empty($productIds)) {
            return ['data' => null];
        }
        $data['product_favorite_ids'] = $productIds;
        $result = $this->productModel->search($data, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $productTransformer);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, Request $request, ProductTransformer $productTransformer)
    {
        $result = ProductFavorite::findOrFail($id);
        Log::view($this->model->getTable());
        $productId = Arr::get($result, 'product_id', null);
        if (empty($productId)) {
            return ['data' => null];
        }
        $product = Product::findOrFail($productId);
        return $this->response->item($product, $productTransformer);
    }

    public function create(Request $request, ProductFavoriteCreateValidator $validator)
    {
        $input = $request->all();
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $isExists = ProductFavorite::model()->where(['user_id' => TM::getCurrentUserId(), 'product_id' => $input['product_id']])->first();
            if (!empty($isExists)) {
                $product_name = Arr::get($isExists, 'product.name', null);
                return ['status' => Message::get("V058", $product_name)];
            }
            $result = $this->model->create([
                'user_id'    => TM::getCurrentUserId(),
                'product_id' => $input['product_id'],
                'created_by' => TM::getCurrentUserId() ?? null,
            ]);
            $product_name = Arr::get($result, 'product.name', null);
            Log::create($this->model->getTable(), $product_name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("V057", $product_name)];
    }

    public function update($id, Request $request, ProductFavoriteUpdateValidator $validator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $isExists = ProductFavorite::model()->where(['user_id' => TM::getCurrentUserId(), 'product_id' => $input['product_id']])->first();
            if (!empty($isExists) && $isExists->id != $id) {
                $product_name = Arr::get($isExists, 'product.name', null);
                return ['status' => Message::get("V058", $product_name)];
            }
            $result = $this->model->update([
                'user_id'    => $input['user_id'],
                'product_id' => $input['product_id'],
            ]);
            $product_name = Arr::get($result, 'product.name', null);
            Log::update($this->model->getTable(), $product_name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $product_name)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = ProductFavorite::where('product_id', $id)->where('user_id', TM::getCurrentUserId())->first();
            $product_name = Arr::get($result, 'product.name', null);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            //Delete
            $result->delete();
            Log::delete($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("V059", $product_name)];
    }
}
