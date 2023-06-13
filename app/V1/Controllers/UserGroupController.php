<?php
/**
 * User: dai.ho
 * Date: 14/05/2020
 * Time: 4:27 PM
 */

namespace App\V1\Controllers;


use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\Supports\DataUser;
use App\UserGroup;
use App\V1\Models\UserGroupModel;
use App\V1\Transformers\UserGroup\UserGroupTransformer;
use App\V1\Validators\UserGroup\UserGroupCreateValidator;
use App\V1\Validators\UserGroup\UserGroupUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserGroupExport;
class UserGroupController extends BaseController
{
    /**
     * @var UserGroupModel
     */
    protected $model;

    /**
     * CustomerGroupController constructor.
     */
    public function __construct()
    {
        /** @var UserGroupModel model */
        $this->model = new UserGroupModel();
    }

    /**
     * @param Request $request
     * @param UserGroupTransformer $userGroupTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, UserGroupTransformer $userGroupTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        $input['company_id'] = TM::getCurrentCompanyId();
        $customerGroup = $this->model->search($input, [], $limit);
        return $this->response->paginator($customerGroup, $userGroupTransformer);
    }
    public function searchClient(Request $request, UserGroupTransformer $userGroupTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        $input['is_view_app'] = 1;
        $input['is_active']   = 1;
        $input['company_id'] = $company_id;
        $customerGroup = $this->model->search($input, [], $limit);
        return $this->response->paginator($customerGroup, $userGroupTransformer);
    }

    public function detail($id, UserGroupTransformer $userGroupTransformer)
    {
        $customerGroup = UserGroup::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
        if (empty($customerGroup)) {
            return ['data' => []];
        }
        return $this->response->item($customerGroup, $userGroupTransformer);
    }

    public function create(
        Request $request,
        UserGroupCreateValidator $userGroupCreateValidator,
        UserGroupTransformer $userGroupTransformer
    )
    {
        $input = $request->all();
        $userGroupCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $userGroupCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $customerGroup = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($customerGroup, $userGroupTransformer);
    }

    public function update(
        $id,
        Request $request,
        UserGroupUpdateValidator $userGroupUpdateValidator,
        UserGroupTransformer $userGroupTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $userGroupUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $userGroupUpdateValidator->validate($input);

        try {
            $userGroup = UserGroup::model()->where('code', $input['code'])->where('company_id', TM::getCurrentCompanyId())->first();
            if (!empty($userGroup) && $userGroup->id != $id) {
                return $this->response->errorBadRequest(Message::get("V008", 'Code'));
            }
            DB::beginTransaction();
            $customerGroup = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($customerGroup, $userGroupTransformer);
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
            $customerGroup = UserGroup::find($id);
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
        return ['status' => Message::get("R003", $customerGroup->code)];
    }
    public function userGroupExportExcel(){
        //ob_end_clean();
        $userGroup = UserGroup::model()->get();
        //ob_start(); // and this
        return Excel::download(new UserGroupExport($userGroup), 'list_userGroup.xlsx');
    }

}