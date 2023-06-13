<?php

namespace App\V1\Controllers;

use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\PollModel;
use App\V1\Transformers\Poll\PollPerformDetailTransformer;
use App\V1\Transformers\Poll\PollPerformTransformer;
use App\V1\Transformers\Poll\PollsTransformer;
use App\V1\Transformers\Poll\PollTransformer;
use App\V1\Validators\PollPerformValidator;
use App\V1\Validators\PollValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends BaseController {
    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->model = new PollModel();
    }

    public function getAll(Request $request, PollsTransformer $transformer) {
        try {
            $input = $request->all();
            $limit = array_get($input, 'limit', 20);
            if (!empty($input['name'])) {
                $input['name'] = ['like' => $input['name']];
            }
            if (!empty($input['code'])) {
                $input['code'] = ['like' => $input['code']];
            }
            $input['company_id'] = TM::getCurrentCompanyId();
            $polls               = $this->model->search($input, ['questions', 'performers'], $limit);
            Log::view($this->model->getTable());

            return $this->response->paginator($polls, $transformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function show(PollTransformer $transformer, $pollId) {
        try {
            $poll = $this->model->getFirstWhere([
                    'id'         => $pollId,
                    'company_id' => TM::getCurrentCompanyId()
            ], ['questions', 'questions.answers']);
            if (!$poll) {
                return ['data' => []];
            }
            Log::view($this->model->getTable(), "#ID:" . $poll->id);

            return $this->response->item($poll, $transformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function showByCode(PollTransformer $transformer, $code) {
        try {
            $poll = $this->model->getFirstWhere([
                    'code'       => $code,
                    'company_id' => TM::getCurrentCompanyId()
            ], ['questions', 'questions.answers']);
            if (!$poll) {
                return ['data' => null];
            }
            Log::view($this->model->getTable(), "#ID:" . $poll->id);

            return $this->response->item($poll, $transformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function store(Request $request) {
        // Validate
        $input     = $request->json()->all();
        $validator = new PollValidator();
        $validator->validate($input);

        try {
            $result = $this->model->createPoll($input);

            Log::create($this->model->getTable(), "#ID:" . $result->id);
            return ['status' => Message::get("poll.create-success", $input['name'])];

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function update(Request $request, $pollId) {
        // Validate
        $input     = $request->json()->all();
        $validator = new PollValidator($pollId);
        $validator->validate($input);

        try {
            $poll = $this->model->getFirstBy('id', $pollId, ['questions', 'questions.answers']);
            if (!$poll) {
                return ['data' => []];
            }

            $result = $this->model->updatePoll($input, $poll);

            Log::create($this->model->getTable(), "#ID:" . $result->id);
            return ['status' => Message::get("poll.update-success", $input['name'])];

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function delete($pollId) {
        try {
            DB::beginTransaction();

            $poll = $this->model->getFirstBy('id', $pollId);
            if (!$poll) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$pollId"));
            }

            // Delete Answers
            foreach ($poll->questions as $item) {
                $item->answers()->delete();
            }

            // Delete Question
            $poll->questions()->delete();

            // Delete Poll
            $poll->delete();
            Log::delete($this->model->getTable(), "#ID:" . $poll->id);

            DB::commit();

            return ['status' => Message::get("poll.delete-success", $poll['name'])];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function perform(Request $request, PollPerformValidator $validator, $pollId) {
        $input = $request->json()->all();
        $validator->validate($input);

        try {
            $poll = $this->model->getFirstBy('id', $pollId, ['questions', 'questions.answers']);
            if (!$poll) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$pollId"));
            }

            $this->model->perform($input, $poll);

            return ['status' => Message::get("poll.perform-success", $poll['name'])];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function performByCode(Request $request, PollPerformValidator $validator, $code) {
        $input = $request->json()->all();
        $validator->validate($input);

        try {
            $poll = $this->model->getFirstBy('code', $code, ['questions', 'questions.answers']);
            if (!$poll) {
                return ['data' => null];
            }

            $this->model->perform($input, $poll);

            return ['status' => Message::get("poll.perform-success", $poll['name'])];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function showPerform(PollPerformTransformer $transformer, $pollId) {
        try {
            $poll = $this->model->getFirstBy('id', $pollId, ['performers']);
            if (!$poll) {
                return ['data' => []];
            }

            Log::view($this->model->getTable(), "#ID:" . $poll->id);

            return $this->response->item($poll, $transformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function showPerformByCode(PollPerformTransformer $transformer, $code) {
        try {
            $poll = $this->model->getFirstWhere([
                    'code'       => $code,
                    'company_id' => TM::getCurrentCompanyId()
            ], ['performers']);
            if (!$poll) {
                return ['data' => null];
            }

            Log::view($this->model->getTable(), "#ID:" . $poll->id);

            return $this->response->item($poll, $transformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function showPerformDetail($pollId, $performId) {
        try {
            $poll = $this->model->getFirstBy('id', $pollId, ['questions', 'questions.answers', 'performers']);
            if (!$poll) {
                return ['data' => []];
            }

            Log::view($this->model->getTable(), "#ID:" . $poll->id);

            return $this->response->item($poll, new PollPerformDetailTransformer($performId));
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }
}
