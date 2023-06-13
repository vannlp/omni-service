<?php


namespace App\V1\Controllers;


use App\ProductUser;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\ProductUserModel;
use App\V1\Transformers\ProductUser\ProductUserTransformer;
use App\V1\Validators\ProductUserCreateValidator;
use App\V1\Validators\ProductUserUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductUserController extends BaseController
{
    /**
     * @var ProductUserModel
     */
    protected $model;

    /**
     * ProductController constructor.
     * @param ProductUserModel $model
     */
    public function __construct(ProductUserModel $model)
    {
        $this->model = $model;
    }

    public function search(Request $request, ProductUserTransformer $productUserTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $productUser = $this->model->search($input, ['user', 'product'], $limit);
        return $this->response->paginator($productUser, $productUserTransformer);
    }

    public function detail($id, ProductUserTransformer $productUserTransformer)
    {
        $productUser = ProductUser::find($id);
        if (empty($productUser)) {
            return ['data' => []];
        }
        return $this->response->item($productUser, $productUserTransformer);
    }

    public function create(Request $request, ProductUserCreateValidator $productUserCreateValidator)
    {
        $input = $request->all();
        $productUserCreateValidator->validate($input);
        try {
            $productUser = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("product-users.create-success")];
    }

    public function update($id, Request $request, ProductUserUpdateValidator $productUserUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $productUserUpdateValidator->validate($input);
        try {
            $productUser = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("product-users.update-success")];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $productUser = ProductUser::find($id);
            if (empty($productUser)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $productUser->delete();
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("product-users.delete-success")];
    }
}