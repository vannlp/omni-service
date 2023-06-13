<?php


namespace App\V1\Controllers;

use App\Feedback;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\FeedbackModel;
use App\V1\Transformers\Feedback\FeedbackTransformer;
use App\Supports\TM_Email;
use App\V1\Validators\Feedback\FeedbackCreateValidator;
use App\V1\Validators\Feedback\FeedbackUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FeedbackController extends BaseController
{

    protected $feedbackModel;

    public function __construct()
    {
        $this->feedbackModel = new FeedbackModel();
    }

    public function search(Request $request, FeedbackTransformer $transformer)
    {

        $input            = $request->all();
        $limit            = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result           = $this->feedbackModel->search($input, [], $limit);

        return $this->response->paginator($result, $transformer);
    }

    public function view($id, FeedbackTransformer $transformer)
    {
        $result = Feedback::model()->where(['id' => $id, 'user_id' => TM::getCurrentUserId()])->first();
        if (!$result) {
            return ["data" => null];
        }
        return $this->response->item($result, $transformer);
    }

    public function create(Request $request, FeedbackCreateValidator $createValidator)
    {
        $input = $request->all();
        $createValidator->validate($input);
        $subject = "Hỗ trợ khách hàng";
        config(['mail.from.name' => "Hỗ trợ khách hàng"]);

        try {
            DB::beginTransaction();
            $feedback = $this->feedbackModel->upsert($input);
            $email    = Arr::get($feedback, 'company.email', null);
            $data     = [
                'address'      => Arr::get($feedback, 'company.address', null),
                'email'        => Arr::get($feedback, 'company.email', null),
                'avatar'       => Arr::get($feedback, 'company.avatar', null),
                'company_name' => Arr::get($feedback, 'company.name', null),
                'username'     => Arr::get($feedback, 'user.name', null),
                'telephone'    => Arr::get($feedback, 'user.phone', null),
                'title'        => Arr::get($feedback, 'title', null),
                'content'      => Arr::get($feedback, 'content', null),
            ];
            TM_Email::send("feedback_from_user", $email, $data, null, null, $subject);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }

        return ['status' => Message::get("R001", $feedback->title)];
    }

    public function update($id, Request $request, FeedbackUpdateValidator $updateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $updateValidator->validate($input);

        try {
            DB::beginTransaction();

            $feedback = $this->feedbackModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }

        return ['status' => Message::get("R002", $feedback->title)];
    }

    public function delete($id)
    {
        try {
            $result = Feedback::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }

        return ['status' => Message::get("R003", $result->title)];
    }

    public function reply($id, Request $request)
    {
        try {
            $input = $request->all();
            if (empty($input['content_reply'])) {
                return $this->responseError(Message::get('V001', 'Content'));
            }
            $result = Feedback::findOrFail($id);

            //Sendmail reply to user
            $emailUser = Arr::get($result, 'user.email', null);
            if ($emailUser) {
                $dataSendMail = [
                    'name'          => Arr::get($result, 'user.name', null),
                    'email'         => Arr::get($result, 'user.email', null),
                    'title'         => $result->title,
                    'content_reply' => $input['content_reply'],
                ];
            TM_Email::send('feedback_reply',$emailUser,$dataSendMail,null,null,'Reply Feedback');
            }
            $result->content_reply = $input['content_reply'];
            $result->status        = 'APPROVED';
            $result->save();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return ['status' => Message::get("R018", $result->title)];
    }
}
