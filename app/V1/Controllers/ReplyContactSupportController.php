<?php


namespace App\V1\Controllers;


use App\ContactSupport;
use App\ReplyContactSupport;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\ReplyContactSupportModel;
use App\V1\Transformers\ReplyContactSupport\ReplyContactSupportTransformer;
use App\V1\Validators\ReplyContactSupportCreateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReplyContactSupportController extends BaseController
{
    /**
     * @var ReplyContactSupportModel
     */
    protected $model;

    /**
     * ReplyContactSupportController constructor.
     */
    public function __construct()
    {
        $this->model = new ReplyContactSupportModel();
    }

    public function search(Request $request, ReplyContactSupportTransformer $replyContactSupportTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['contact_support_id'])) {
            $input['contact_support_id'] = ['like' => $input['contact_support_id']];
        }
        if (!empty($input['user_replay'])) {
            $input['user_replay'] = ['like' => $input['user_replay']];
        }
        if (!empty($input['user_replay'])) {
            $input['user_replay'] = ['like' => $input['user_replay']];
        }
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $replyContactSupportTransformer);
    }

    public function detail($id, ReplyContactSupportTransformer $replyContactSupportTransformer)
    {
        $result = ReplyContactSupport::find($id);
        if (empty($card)) {
            return ["data" => []];
        }
        //Log::view($this->model->getTable());
        return $this->response->item($result, $replyContactSupportTransformer);
    }

    public function view($contactId)
    {
        $result = ReplyContactSupport::where('contact_support_id', $contactId)->get();
        if (empty($result)) {
            return ["data" => []];
        }
        $data = [];
        foreach ($result as $item) {
            $data[] = [
                'id'                 => $item->id,
                'contact_support_id' => $item->contact_support_id,
                'content_reply'      => $item->content_reply,
                'created_at'         => date("d-m-Y H:i:s", strtotime($item->created_at)),
                'created_by'         => object_get($item, "createdBy.profile.full_name"),
                'updated_at'         => date("d-m-Y H:i:s", strtotime($item->updated_at)),
                'updated_by'         => object_get($item, "updatedBy.profile.full_name"),
            ];
        }
        //Log::view($this->model->getTable());
        return response()->json(['data' => $data]);
    }

    public function create(Request $request, ReplyContactSupportCreateValidator $replyContactSupportCreateValidator)
    {
        $input = $request->all();
        $replyContactSupportCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            //Log::create($this->model->getTable(), "#ID:" . $card->id . "-" . $card->code . "-" . $card->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("cards.create-success", $result->content_reply)];
    }
}