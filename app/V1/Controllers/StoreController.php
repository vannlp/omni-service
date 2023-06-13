<?php
/**
 * User: kpistech2
 * Date: 2019-11-03
 * Time: 15:21
 */

namespace App\V1\Controllers;


use App\Company;
use App\Product;
use App\ProductStore;
use App\Role;
use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\UserCompany;
use App\UserStore;
use App\V1\Models\StoreModel;
use App\V1\Models\UserModel;
use App\V1\Transformers\Product\ProductTransformer;
use App\V1\Transformers\Store\MyStoreTransformer;
use App\V1\Transformers\Store\StoreTransformer;
use App\V1\Transformers\User\UserTransformer;
use App\V1\Validators\StoreCreateValidator;
use App\V1\Validators\StoreUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StoreController extends BaseController
{

    protected $model;

    /**
     * StoreController constructor.
     * @param StoreModel $model
     */
    public function __construct(StoreModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param StoreTransformer $storeTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, StoreTransformer $storeTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['company_id'])) {
            $input['company_id'] = ['=' => $input['company_id']];
        }

        if (!empty($input['store_id'])) {
            $input['id'] = ['=' => $input['store_id']];
        }

        $input['company_id'] = TM::getCurrentCompanyId();

        $store = $this->model->search($input, [], $limit);
        return $this->response->paginator($store, $storeTransformer);
    }

    /**
     * @param $id
     * @param StoreTransformer $storeTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, StoreTransformer $storeTransformer)
    {
        $store = Store::find($id);
        if (empty($store)) {
            return ['data' => []];
        }
        return $this->response->item($store, $storeTransformer);
    }

    /**
     * @param Request $request
     * @param StoreCreateValidator $storeCreateValidator
     * @return array|void
     */
    public function create(Request $request, StoreCreateValidator $storeCreateValidator)
    {
        $input = $request->all();
        $storeCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $storeCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $store = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("stores.create-success", $store->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param StoreUpdateValidator $storeUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, StoreUpdateValidator $storeUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $storeUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $storeUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $store = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("stores.update-success", $store->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $store = Store::find($id);
            if (empty($store)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $store->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("stores.delete-success", $store->name)];
    }

    ////////////////////////// MY STORE /////////////////////
    public function getMyStore(Request $request, StoreTransformer $storeTransformer)
    {
        $input = $request->all();

        try {
            $myStoreId = UserStore::model()->where('user_id', TM::getCurrentUserId())->get()
                ->pluck('store_id')->toArray();
            $input['id'] = ['in' => !empty($myStoreId) ? $myStoreId : ['-1']];
            if (!empty($input['company_id'])) {
                $input['company_id'] = ['=' => $input['company_id']];
            }
            $stores = $this->model->search($input, [], array_get($input, 'limit', 20));
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($stores, $storeTransformer);
    }

    public function getMyStoreDetail($id, Request $request, MyStoreTransformer $myStoreTransformer)
    {
        $input = $request->all();
        $userStore = UserStore::model()->where('user_id', TM::getCurrentUserId())->where('store_id', $id);
        $input['company_id'] = TM::getCurrentCompanyId();
        if (!empty($input['company_id'])) {
            $userStore->where('company_id', $input['company_id']);
        }
        $userStore = $userStore->first();
        if (empty($userStore)) {
            return ['data' => null];
        }
        $store = Store::find($id);
        if (empty($store)) {
            return ['data' => null];
        }
        return $this->response->item($store, $myStoreTransformer);
    }

    ########################### USER STORE ######################
    public function listUsers($id, Request $request, UserTransformer $userTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        try {
            $userStores = UserStore::model()->where('store_id', $id)->get()->pluck('store_id', 'user_id')->toArray();

            if (empty($userStores[TM::getCurrentUserId()])) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("stores")));
            }
            $store = Store::model()->where('id', $id)->first();
            if (!$store) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("stores")));
            }
            $userModel = new UserModel();
            $input['id'] = $id;
            $users = $userModel->searchStore($input, [], $limit);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->paginator($users, $userTransformer);
    }

    public function addUsers($id, Request $request)
    {
        $input = $request->all();
        if (empty($input['user_id'])) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get("users")));
        }
        try {
            DB::beginTransaction();
            $myStore = UserStore::model()->where(['store_id' => $id, 'user_id' => TM::getCurrentUserId()])->first();
            if (!$myStore) {
                return $this->response->errorBadRequest(Message::get("V001", Message::get("stores")));
            }
            $store = Store::model()->where('id', $id)->first();
            if (!$store) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("stores")));
            }
            $user = User::model()->with(['profile'])->where('id', $input['user_id'])->first();
            if (!$user) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("users")));
            }

            $now = date('Y-m-d H:i:s', time());
            $userStore = UserStore::model()->where(['store_id' => $id, 'user_id' => $input['user_id']])->first();
            if (!$userStore) {
                $role = Role::model()->where('code', USER_ROLE_ADMIN)->first();
                $company = Company::model()->where('id', $store->company_id)->first();
                $userStore = new UserStore();
                $userStore->user_id = $input['user_id'];
                $userStore->role_id = $role->id;
                $userStore->company_id = $company->id;
                $userStore->store_id = $id;
                $userStore->user_code = $user->code;
                $userStore->user_name = $user->profile->full_name ?? null;
                $userStore->store_code = $store->code;
                $userStore->store_name = $store->name;
                $userStore->company_code = $company->code;
                $userStore->company_name = $company->name;
                $userStore->role_code = $role->code;
                $userStore->role_name = $role->name;
                $userStore->created_at = $now;
                $userStore->created_by = TM::getCurrentUserId();
                $userStore->save();
            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("stores.update-success", $store->name)];
    }

    public function deleteUsers($id, Request $request)
    {
        $input = $request->all();
        if (empty($input['user_id'])) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get("users")));
        }
        try {
            DB::beginTransaction();
            $myStore = UserStore::model()->where(['store_id' => $id, 'user_id' => TM::getCurrentUserId()])->first();
            if (!$myStore) {
                return $this->response->errorBadRequest(Message::get("V001", Message::get("stores")));
            }
            $store = Store::model()->where('id', $id)->first();
            if (!$store) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("stores")));
            }
            $user = User::model()->where('id', $input['user_id'])->first();
            if (!$user) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("users")));
            }

            $userStore = UserStore::model()->where(['store_id' => $id, 'user_id' => $input['user_id']])->first();
            if (!$userStore) {
                return $this->response->errorBadRequest(Message::get("V002", Message::get("user_stores")));
            }
            $userStore->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("stores.delete-success", $user->code)];
    }

    public function getStoreToken($code)
    {
        $store = Store::model()->where('code', $code)->first();
        if (empty($store)) {
            return $this->responseError(Message::get("V002", Message::get("stores")));
        }

        return response()->json([
            'data' => [
                'code'           => $store->code,
                'name'           => $store->name,
                'store_token'    => $store->token,
                'company_id'     => $store->company_id,
                'company_code'   => object_get($store, 'company.code'),
                'company_name'   => object_get($store, 'company.name'),
                'company_avatar' => object_get($store, 'company.avatar'),
            ],
        ]);
    }

    public function getStoreProductToken(Request $request, ProductTransformer $productTransformer)
    {
        $input = $request->all();
        $store = Store::model()->where('token', $input['store_token'])->first();
        $limit = array_get($input, 'limit', 20);
        if (empty($store)) {
            return $this->response->errorBadRequest(Message::get("V056"));
        }
        $storeId = $store->id;
        $productStore = ProductStore::model()->where('store_id', $storeId)->pluck('product_id', 'product_id')->toArray();
        $product = Product::model()->whereIn('id', $productStore)->limit($limit)->get();
        return response()->json(['data' => $product]);
    }

    public function listAllStore(Request $request, StoreTransformer $storeTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }

        $store = $this->model->search($input, [], $limit);
        return $this->response->paginator($store, $storeTransformer);
    }
}
