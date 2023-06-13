<?php


namespace App\V1\Controllers;


use App\Discuss;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\DiscussModel;
use App\V1\Transformers\Discuss\DiscussTransformer;
use App\V1\Validators\Discuss\DiscussCreateValidator;
use App\V1\Validators\Discuss\DiscussUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscussController extends BaseController
{
    /**
     * @var DiscussModel
     */
    protected $model;

    /**
     * IssueController constructor.
     */
    public function __construct()
    {
        $this->model = new DiscussModel();
    }

    /**
     * @param Request $request
     * @param DiscussTransformer $discussTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, DiscussTransformer $discussTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $result = $this->model->search($input, [], $limit);
        //  Log::view($this->model->getTable());
        return $this->response->paginator($result, $discussTransformer);
    }

    public function detailDiscuss($id, DiscussTransformer $discussDetailTransformer)
    {
        try {
            $result = $this->model->getFirstBy('id', $id);
            //   Log::view($this->model->getTable());
            if (empty($result)) {
                return ["data" => []];
            }
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($result, $discussDetailTransformer);
    }

    public function countLike($id)
    {
        try {
            $result = Discuss::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $result->increment('count_like');
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        if (empty($result)) {
            return ["data" => []];
        }
        return response()->json(['data' => $result]);
    }

    public function detail($issue_id)
    {
        try {
            $result = Discuss::model()->where('issue_id', $issue_id)->get();
            //  Log::view($this->model->getTable());
            foreach ($result as $value) {

                $detail = Discuss::model()->where('parent_id', $value->id)->get();
                $avatar = object_get($value, "createdBy.profile.avatar");
                $avatar = !empty($avatar) ? url('/v0') . "/img/" . $avatar : null;

                $folder_path = object_get($value, 'file.folder.folder_path');
                if (!empty($folder_path)) {
                    $folder_path = str_replace("/", ",", $folder_path);
                } else {
                    $folder_path = "uploads";
                }
                $folder_path = url('/v0') . "/img/" . $folder_path;
                $file_name = object_get($value, 'file.file_name');
                $data = [];
                foreach ($detail as $item) {
                    $fullName = object_get($item, "createdBy.profile.full_name");
                    $avatarChild = object_get($item, "createdBy.profile.avatar");
                    $avatarChild = !empty($avatarChild) ? url('/v0') . "/img/" . $avatarChild : null;
                    $data[] = [
                        'id'          => $item->id,
                        'issue_id'    => $item->issue_id,
                        'description' => $item->description,
                        'is_active'   => $item->is_active,
                        'created_by'  => $fullName,
                        'avatar'      => $avatarChild,
                        'user_id'     => $item->created_by,
                        'parent_id'   => $item->parent_id,
                        'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
                        'file'        => !empty($file_name) ? $folder_path . ',' . $file_name : null,
                    ];
                }
                if (empty($value->parent_id)) {
                    $resultJson[] = [
                        'id'          => $value->id,
                        'issue_id'    => $value->issue_id,
                        'description' => $value->description,
                        'is_active'   => $value->is_active,
                        'user_id'     => $value->created_by,
                        'parent_id'   => $value->parent_id,
                        'avatar'      => $avatar,
                        'created_by'  => object_get($value, 'createdBy.profile.full_name'),
                        'detail'      => $data,
                        'updated_at'  => date('d/m/Y H:i', strtotime($value->updated_at)),
                        'file'        => !empty($file_name) ? $folder_path . ',' . $file_name : null,
                    ];
                }
            }
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        if (empty($resultJson)) {
            return ["data" => []];
        }
        return response()->json(['data' => $resultJson]);
    }


    public function create(Request $request, DiscussCreateValidator $discussCreateValidator, DiscussTransformer $discussTransformer)
    {
        $input = $request->all();
        $discussCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), $result->description);
            DB::commit();
//            $this->createNotify($result);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $discussTransformer);
    }

//    public function createNotify(Discuss $discuss)
//    {
//        $now = time();
//        $data = [
//            'discuss_issue_id' => $discuss->issue_id,
//            'discuss_id'       => $discuss->parent_id ?? $discuss->id,
//            'title'            => "bình luận",
//            'sender'           => $discuss->created_by,
//            'receiver'         => !empty($discuss->parent_id) ? $this->getIdCreateDiscuss($discuss->parent_id) : $this->getIdCreateIssue($discuss->issue_id),
//            'description'      => $discuss->description,
//            'date'             => date("Y-m-d H:i:s", $now),
//            'is_active'        => 1,
//        ];
//        $notifies = new NotifyModel();
//        $notifies->create($data);
//    }

//    private function getIdCreateDiscuss($id)
//    {
//        if (empty($id)) {
//            return [];
//        }
//        $idCreateDiscuss = Discuss::model()->where('id', $id)->get();
//        $idCreateDiscuss = array_pluck($idCreateDiscuss, 'created_by');
//        $idCreateDiscuss = implode(', ', $idCreateDiscuss);
//        return $idCreateDiscuss;
//    }

//    private function getIdCreateIssue($id)
//    {
//        if (empty($id)) {
//            return [];
//        }
//        $IdCreateIssue = Issue::model()->where('id', $id)->get();
//        $IdCreateIssue = array_pluck($IdCreateIssue, 'created_by');
//        $IdCreateIssue = implode(', ', $IdCreateIssue);
//        return $IdCreateIssue;
//    }

    public function update(
        $id,
        Request $request,
        DiscussUpdateValidator $discussUpdateValidator,
        DiscussTransformer $discussTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $discussUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::update($this->model->getTable(), $result->description);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $discussTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = Discuss::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // Delete Discuss child parent
            // 1. Delete Order detail
            Discuss::model()->where('parent_id', $id)->delete();
            // 1. Delete Discuss
            $result->delete();

            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->id)];
    }
}