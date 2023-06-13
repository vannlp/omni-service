<?php

namespace App\Sync\Controllers;

use App\Sync\Models\NinjaSyncFilterInteractiveModel;
use App\Sync\Models\NinjaSyncFilterPostModel;
use App\Sync\Models\NinjaSyncListCommentModel;
use App\Sync\Models\NinjaSyncListMemberGroupModel;
use App\Sync\Models\NinjaSyncListPageIdModel;
use App\Sync\Models\NinjaSyncListUserModel;
use App\Sync\Models\NinjaSyncUidFriendModel;
use App\Sync\Models\NinjaSyncUidPostModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Supports\TM_Error;
use App\Supports\Message;
use App\Sync\Models\NinjaSyncModel;
use App\Sync\Transformers\NinjaSyncTransformer;
use App\Sync\Models\NinjaSyncListGroupModel;
use App\Sync\Models\NinjaSyncListLiveModel;
use App\Sync\Models\NinjaSyncUidAnalysisModel;
use App\Sync\Validators\ArticleInteractionCreateValidator;

class NinjaSyncController extends NinjaSyncBaseController
{
    protected $ninjaSyncModel;
    protected $ninjaSyncListGroupModel;
    protected $ninjaSyncListMemberGroupModel;
    protected $ninjaSyncListLiveModel;
    protected $ninjaSyncUidFriendModel;
    protected $ninjaSyncUidAnalysisModel;
    protected $ninjaSyncFilterPostModel;
    protected $ninjaSyncUidPostModel;
    protected $ninjaSyncListUserModel;
    protected $ninjaSyncFilterInteractiveModel;
    protected $ninjaSyncListCommentModel;
    protected $ninjaSyncListPageIdModel;


    public function __construct(Request $request)
    {
        $this->ninjaSyncModel                  = new NinjaSyncModel();
        $this->ninjaSyncListGroupModel         = new NinjaSyncListGroupModel();
        $this->ninjaSyncListMemberGroupModel   = new NinjaSyncListMemberGroupModel();
        $this->ninjaSyncListLiveModel          = new NinjaSyncListLiveModel();
        $this->ninjaSyncUidFriendModel         = new NinjaSyncUidFriendModel();
        $this->ninjaSyncUidAnalysisModel       = new NinjaSyncUidAnalysisModel();
        $this->ninjaSyncFilterPostModel        = new NinjaSyncFilterPostModel();
        $this->ninjaSyncUidPostModel           = new NinjaSyncUidPostModel();
        $this->ninjaSyncListUserModel          = new NinjaSyncListUserModel();
        $this->ninjaSyncFilterInteractiveModel = new NinjaSyncFilterInteractiveModel();
        $this->ninjaSyncListCommentModel       = new NinjaSyncListCommentModel();
        $this->ninjaSyncListPageIdModel       = new NinjaSyncListPageIdModel();
        parent::__construct($request);

    }


    public function search(Request $request, NinjaSyncTransformer $ninjaSyncTransformer)
    {

        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->ninjaSyncModel->search($input, [], $limit);

        return $this->response->paginator($result, $ninjaSyncTransformer);
    }


    public function articleInteraction(Request $request)
    {
        $input = $request->all();

        try {
            DB::beginTransaction();
            $ninjaSyncModel = $this->ninjaSyncModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncModel->name)];

    }

    public function listGroup(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncListGroupModel = $this->ninjaSyncListGroupModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncListGroupModel->name)];

    }

    public function listMemberGroup(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncListMemberGroupModel = $this->ninjaSyncListMemberGroupModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncListMemberGroupModel->name)];

    }

    public function listLive(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncListLiveModel = $this->ninjaSyncListLiveModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncListLiveModel->name)];

    }

    public function uidFriend(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncUidFriendModel = $this->ninjaSyncUidFriendModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncUidFriendModel->name)];

    }

    public function createUidAnalysis(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncUidAnalysisModel = $this->ninjaSyncUidAnalysisModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncUidAnalysisModel->name)];

    }

    public function createFilterPost(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncFilterPostModel = $this->ninjaSyncFilterPostModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncFilterPostModel->name)];

    }

    public function createUidPost(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncUidPostModel = $this->ninjaSyncUidPostModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncUidPostModel->name)];

    }

    public function createListUser(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncListUserModel = $this->ninjaSyncListUserModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncListUserModel->name)];

    }

    public function createFilterInteractive(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncFilterInteractiveModel = $this->ninjaSyncFilterInteractiveModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncFilterInteractiveModel->name)];

    }

    public function createListComment(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncListCommentModel = $this->ninjaSyncListCommentModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncListCommentModel->name)];

    }

    public function createListPageId(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $ninjaSyncListPageIdModel = $this->ninjaSyncListPageIdModel->upsert($input);

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ninjaSyncListPageIdModel->name)];

    }
}
