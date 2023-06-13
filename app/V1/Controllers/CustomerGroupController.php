<?php


namespace App\V1\Controllers;


use App\CustomerGroup;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Validators\CustomerGroupCreateValidator;
use App\V1\Validators\CustomerGroupUpdateValidator;
use App\V1\Transformers\CustomerGroup\CustomerGroupTransformer;
use App\V1\Models\CustomerGroupModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerGroupController extends BaseController
{
    /**
     * @var CustomerGroupModel
     */
    protected $model;

    /**
     * CustomerGroupController constructor.
     */
    public function __construct()
    {
        /** @var CustomerGroupModel model */
        $this->model = new CustomerGroupModel();
    }
    /**
     * @param Request $request
     * @param CustomerGroupTransformer $customerGroupTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, CustomerGroupTransformer $customerGroupTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        $customerGroup = $this->model->search($input, [], $limit);
        return $this->response->paginator($customerGroup, $customerGroupTransformer);
    }

    public function detail($id, CustomerGroupTransformer $customerGroupTransformer)
    {
        $customerGroup = CustomerGroup::find($id);
        if (empty($customerGroup)) {
            return ['data' => []];
        }
        return $this->response->item($customerGroup, $customerGroupTransformer);
    }

    public function create(
        Request $request,
        CustomerGroupCreateValidator $customerGroupCreateValidator,
        CustomerGroupTransformer $customerGroupTransformer
    ) {
        $input = $request->all();
        $customerGroupCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $customerGroup = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($customerGroup, $customerGroupTransformer);
    }

    public function update(
        $id,
        Request $request,
        CustomerGroupUpdateValidator $customerGroupUpdateValidator,
        CustomerGroupTransformer $customerGroupTransformer
    ) {
        $input = $request->all();
        $input['id'] = $id;
        $customerGroupUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $customerGroup = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($customerGroup, $customerGroupTransformer);
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
            $customerGroup = CustomerGroup::find($id);
            if (empty($customerGroup)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            // 1. Delete CustomerGroup
            $customerGroup->delete();
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("customer_groups.delete-success", $customerGroup->code)];
    }
}