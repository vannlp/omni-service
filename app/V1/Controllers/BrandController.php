<?php

namespace App\V1\Controllers;

use App\Brand;
use App\Exports\BrandExport;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\TM;
use App\V1\Models\BrandModel;
use App\V1\Transformers\Brand\BrandClientTransformer;
use App\V1\Transformers\Brand\BrandTransformer;
use App\V1\Validators\BrandCreateValidator;
use App\V1\Validators\BrandUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class BrandController extends BaseController
{
    /**
     * @var BrandModel $model
     */
    protected $model;

    /**
     * BrandController constructor.
     *
     * @param BrandModel|null $brandModel
     */
    public function __construct(BrandModel $brandModel = null)
    {
        $this->model = $brandModel ?: new BrandModel();
    }

    /**
     * Search
     *
     * @param Request $request
     * @param BrandTransformer $brandTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, BrandTransformer $brandTransformer)
    {
        $input             = $request->all();
        $input['store_id'] = TM::getCurrentStoreId();
        $limit             = array_get($input, 'limit', 20);
        if (!empty($input['is_parent'])) {
            $input['parent_id'] = ['=' => null];
            unset($input['is_parent']);
        }
        Log::view($this->model->getTable());
        $brands = $this->model->search($input, ['userCreated:id,name', 'userUpdated:id,name'], $limit);
        return $this->response->paginator($brands, $brandTransformer);
    }

    /**
     * Get company id
     *
     * @param Request $request
     * @return |null
     */
    private function getCompanyId(Request $request)
    {
        $companyId = null;

        if (TM::getCurrentUserId()) {
            $companyId = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if ($store) {
                        $companyId = $store->company_id;
                    }
                }
            }
        }
        return $companyId;
    }

    private function getStoreId(Request $request)
    {
        $storeId = null;

        if (TM::getCurrentStoreId()) {
            $storeId = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if ($store) {
                        $storeId = $store->id;
                    }
                }
            }
        }
        return $storeId;
    }

    /**
     * Client search
     *
     * @param Request $request
     * @param BrandClientTransformer $brandClientTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function clientSearch(Request $request, BrandClientTransformer $brandClientTransformer)
    {
        $input  = $request->all();
        $brands = $this->model->getModel()
            ->select(['id', 'name', 'description'])
            ->where('company_id', $this->getCompanyId($request))
            ->where('parent_id', null)
            ->where(function ($query) use ($input) {
                if (!empty($input['name'])) {
                    $query->where('name', 'like', '%' . $input['name'] . '%');
                }

                if (!empty($input['id'])) {
                    $query->where('id', '=', $input['id']);
                }
            })
            ->paginate(Arr::get($input, 'limit', 20));

        return $this->response->paginator($brands, $brandClientTransformer);
    }

    /**
     * Show
     *
     * @param $id
     * @param BrandTransformer $brandTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function show($id, BrandTransformer $brandTransformer)
    {
        $brand = $this->model->getFirstBy('id', $id, ['userCreated:id,name', 'userUpdated:id,name', 'children']);

        Log::view($this->model->getTable());
        return $this->response->item($brand, $brandTransformer);
    }

    /**
     * Store
     *
     * @param Request $request
     * @param BrandCreateValidator $brandCreateValidator
     * @return array
     */
    public function store(Request $request, BrandCreateValidator $brandCreateValidator)
    {
        $input = $request->all();
        $brandCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $brandCreateValidator->validate($input);

        $input['slug']       = $this->model->generateSlug($input['name']);
        $input['company_id'] = $this->getCompanyId($request);

        $brand = $this->model->create($this->model->fillData($input));

        Log::create($this->model->getTable(), "#ID:" . $brand->id);
        return ['status' => Message::get("brands.create-success", $brand->name)];
    }

    /**
     * Update
     *
     * @param $id
     * @param Request $request
     * @param BrandUpdateValidator $brandUpdateValidator
     * @return array
     */
    public function update($id, Request $request, BrandUpdateValidator $brandUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $brandUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $brandUpdateValidator->validate($input);
        $input['slug'] = $this->model->generateSlug($input['name'], $id);

        $model               = $this->model->byId($id);
        $input['company_id'] = $this->getCompanyId($request);

        $model->fill($this->model->fillData($input));
        $model->save();

        Log::update($this->model->getTable(), "#ID:" . $id);
        return ['status' => Message::get("brands.update-success", $model->name)];
    }

    /**
     * Delete
     *
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        $model = $this->model->byId($id);

        if (empty($model)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }

        $model->delete();

        Log::delete($this->model->getTable(), "#ID:" . $id);
        return ['status' => Message::get("brands.delete-success", $model->name)];
    }

    public function getClientBrand(Request $request, BrandClientTransformer $brandClientTransformer)
    {
        $input  = $request->all();
        $brands = $this->model->getModel()
            ->select(['id', 'name', 'description'])
            ->where('store_id', $this->getStoreId($request))
            ->where('parent_id', null)
            ->where(function ($query) use ($input) {
                if (!empty($input['name'])) {
                    $query->where('name', 'like', '%' . $input['name'] . '%');
                }
                if (!empty($input['id'])) {
                    $query->where('id', '=', $input['id']);
                }
            })
            ->paginate(Arr::get($input, 'limit', 20));

        return $this->response->paginator($brands, $brandClientTransformer);
    }

    public function brandExportExcel()
    {
        //ob_end_clean();
        $brands = Brand::model()->get();
        //ob_start();
        return Excel::download(new BrandExport($brands), 'list_brand.xlsx');
    }
}