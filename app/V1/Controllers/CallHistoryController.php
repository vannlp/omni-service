<?php


namespace App\V1\Controllers;


use App\CallHistory;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\CallHistoryModel;
use App\V1\Transformers\CallHistory\CallHistoryReportTransformer;
use App\V1\Transformers\CallHistory\CallHistoryTransformer;
use App\V1\Validators\CallHistory\CallHistoryCreateValidator;
use App\V1\Validators\CallHistory\CallHistoryUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallHistoryController extends BaseController
{
    protected $callHistoryModel;

    public function __construct()
    {
        $this->callHistoryModel = new CallHistoryModel();
    }

    public function search(Request $request, CallHistoryTransformer $transformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);

        $query = $this->callHistoryModel->getModel()
            ->select(
                'call_histories.*',
                'caller.full_name AS caller_name',
                'receiver.full_name AS receiver_name'
            )
            ->join('profiles AS caller', 'caller.user_id', '=', 'call_histories.caller_id')
            ->join('profiles AS receiver', 'receiver.user_id', '=', 'call_histories.receiver_id');

        if (!empty($input['caller_name'])) {
            $query->where('caller.full_name', 'LIKE', "%{$input['caller_name']}%");
        }

        if (!empty($input['receiver_name'])) {
            $query->where('receiver.full_name', 'LIKE', "%{$input['receiver_name']}%");
        }

        if (!empty($input['caller_id'])) {
            $query->where('call_histories.caller_id', $input['caller_id']);
        }

        if (!empty($input['receiver_id'])) {
            $query->where('call_histories.receiver_id', $input['receiver_id']);
        }

        if (!empty($input['call_from_time'])) {
            $query->where('call_from_time', '<=', $input['call_from_time']);
        }

        if (!empty($input['call_to_time'])) {
            $query->where('call_to_time', '>=', $input['call_to_time']);
        }

        if (!empty($input['total_time'])) {
            $query->where('total_time', $input['total_time']);
        }

        if (!empty($input['vote'])) {
            $query->where('vote', $input['vote']);
        }

        if (!empty($input['sort'])) {
            foreach ($input['sort'] as $col => $sort) {
                $column = '';

                switch ($col) {
                    case 'caller_name':
                        $column = 'caller.full_name';
                        break;
                    case 'receiver_name':
                        $column = 'receiver.full_name';
                        break;
                    case 'vote':
                        $column = 'vote';
                        break;
                    case 'total_time':
                        $column = 'total_time';
                        break;
                }

                if (!empty($column)) {
                    $query->addOrderBy($column, $sort);
                }
            }
        }

        if ($limit) {
            if ($limit === 1) {
                $items = $query->first();
            } else {
                $items = $query->paginate($limit);
            }
        } else {
            $items = $query->get();
        }

//        $items = $this->manageCultureModel->search($input, [], $limit);

        Log::view($this->callHistoryModel->getTable());

        return $this->response->paginator($items, $transformer);
    }

    public function create(Request $request, CallHistoryCreateValidator $createValidator, CallHistoryTransformer $transformer)
    {
        $input = $request->all();
        $createValidator->validate($input);

        try {
            DB::beginTransaction();

            $model = $this->callHistoryModel->upsert($input);

            Log::create($this->callHistoryModel->getTable(), $model->call_from_time);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();

            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($model, $transformer);
//        return ['status' => Message::get("categories.create-success", "Cuộc gọi bắt đầu từ " . array_get($input, 'call_from_time', $model->call_from_time))];
    }

    public function update($id, Request $request, CallHistoryUpdateValidator $updateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;

        $updateValidator->validate($input);

        try {
            DB::beginTransaction();

            $model = $this->callHistoryModel->upsert($input);

            Log::update($this->callHistoryModel->getTable(), $model->call_from_time);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();

            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("categories.update-success", "Cuộc gọi bắt đầu từ " . array_get($input, 'call_from_time', $model->call_from_time))];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $model = CallHistory::find($id);

            if (empty($model)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $model->delete();

            Log::delete($this->callHistoryModel->getTable(), $model->call_from_time);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();

            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("categories.delete-success", "Cuộc gọi bắt đầu từ " . $model->call_from_time)];
    }

    public function detail($id, CallHistoryTransformer $transformer)
    {
        try {
            $result = $this->callHistoryModel->getModel()
                ->select(
                    'call_histories.*',
                    'caller.full_name AS caller_name',
                    'receiver.full_name AS receiver_name'
                )
                ->join('profiles AS caller', 'caller.user_id', '=', 'call_histories.caller_id')
                ->join('profiles AS receiver', 'receiver.user_id', '=', 'call_histories.receiver_id')
                ->where('call_histories.id', $id)
                ->first();

            Log::view($this->callHistoryModel->getTable());

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

        return $this->response->item($result, $transformer);
    }

    public function setStopCall($id)
    {
        try {
            $result = CallHistory::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $to_time = date('Y-m-d H:i:s', time());
            $from_time = $result->call_from_time;
            $from_date = new \DateTime($from_time);
            $to_date = new \DateTime($to_time);
            $total_time = $to_date->getTimestamp() - $from_date->getTimestamp();
            DB::beginTransaction();
            $result->call_end_time = date('Y-m-d H:i:s', time());
            $result->total_time = gmdate("H:i:s", $total_time);
            $result->save();
            DB::commit();
            return ['status' => Message::get("categories.update-success", "Cuộc gọi bắt đầu từ " . $result->call_from_time)];
        } catch (\Exception $ex) {
            DB::rollBack();
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
    }

    public function report(Request $request, CallHistoryReportTransformer $transformer)
    {
        try {
            $input = $request->all();
            $limit = array_get($input, 'limit', 20);

            $query = $this->callHistoryModel->getModel()
                ->select(
                    DB::raw('SUM(vote) AS total_vote'),
                    DB::raw('COUNT(call_histories.id) AS total_call'),
                    'receiver.full_name AS receiver_name',
                    'call_histories.receiver_id AS user_id',
                    'receiver.email'
                )
                ->join('profiles AS receiver', 'receiver.user_id', '=', 'call_histories.receiver_id')
                ->whereNotNull('vote')
                ->whereRaw('vote > 0')
                ->groupBy('call_histories.receiver_id');

            if (!empty($input['receiver_id'])) {
                $query->where('call_histories.receiver_id', $input['receiver_id']);
            }

            $result = $query->paginate($limit);

            if (empty($result)) {
                return ["data" => []];
            }

            return $this->response->paginator($result, $transformer);

        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($result, $transformer);
    }

    public function vote($id, Request $request)
    {
        $input = $request->all();
        if (empty($input['vote'])) {
            return $this->response->errorBadRequest(Message::get('V001', Message::get('vote')));
        }
        $result = CallHistory::find($id);
        if (empty($result)) {
            return $this->response->errorBadRequest(Message::get('V003', 'ID #' . $id));
        }
        if (!is_numeric($input['vote']) || $input['vote'] <= 0 || $input['vote'] > 5) {
            return $this->response->errorBadRequest(Message::get('V002', Message::get('vote')));
        }
        try {
            DB::beginTransaction();
            $result->vote = $input['vote'];
            $result->save();
            DB::commit();
            return ['status' => Message::get("callhistories.vote-success", "Cuộc gọi bắt đầu từ " . $result->call_from_time)];
        } catch (\Exception $ex) {
            DB::rollBack();
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
    }
}