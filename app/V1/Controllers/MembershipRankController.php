<?php


namespace App\V1\Controllers;


use App\MembershipRank;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\MembershipRankModel;
use App\V1\Transformers\MembershipRank\MembershipRankTransformer;
use App\V1\Validators\MembershipRankCreateValidator;
use App\V1\Validators\MembershipRankUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembershipRankController extends BaseController
{
    /**
     * @var MembershipRankModel
     */
    protected $model;

    /**
     * MembershipRankController constructor.
     */
    public function __construct()
    {
        $this->model = new MembershipRankModel();
    }

    /**
     * @param Request $request
     * @param MembershipRankTransformer $membershipRankTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, MembershipRankTransformer $membershipRankTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $membershipRank = $this->model->search($input, [], $limit);
        return $this->response->paginator($membershipRank, $membershipRankTransformer);
    }

    /**
     * @param $id
     * @param MembershipRankTransformer $customerTypeTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, MembershipRankTransformer $customerTypeTransformer)
    {
        $membershipRank = MembershipRank::find($id);
        if (empty($membershipRank)) {
            return ['data' => []];
        }
        return $this->response->item($membershipRank, $customerTypeTransformer);
    }

    public function create(Request $request, MembershipRankCreateValidator $membershipRankCreateValidator)
    {
        $input = $request->all();
        $membershipRankCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $membershipRankCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $membershipRank = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("membership_ranks.create-success", $membershipRank->code)];
    }

    public function update($id, Request $request, MembershipRankUpdateValidator $membershipRankUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $membershipRankUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $membershipRankUpdateValidator->validate($input);
        $membershipRank = MembershipRank::model()->where('code', $input['code'])->where('company_id', TM::getCurrentCompanyId())->first();
        if (!empty($membershipRank) && $membershipRank->id != $id) {
            return $this->response->errorBadRequest(Message::get("V008", 'Code'));
        }
        try {
            DB::beginTransaction();
            $membershipRank = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("membership_ranks.update-success", $membershipRank->code)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $membershipRank = MembershipRank::find($id);
            if (empty($membershipRank)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            // 1. Delete CustomerType
            $membershipRank->delete();
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("membership_ranks.delete-success", $membershipRank->code)];
    }
}