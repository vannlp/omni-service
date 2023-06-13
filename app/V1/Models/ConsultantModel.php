<?php


namespace App\V1\Models;


use App\Consultant;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;

class ConsultantModel extends AbstractModel
{
    public function __construct(Consultant $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $consultant = Consultant::find($id);
                if (empty($consultant)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $consultant->user_id = array_get($input, 'user_id', $consultant->user_id);
                $consultant->consultant_id = array_get($input, 'consultant_id', $consultant->consultant_id);
                $consultant->title = array_get($input, 'title', $consultant->title);
                $consultant->company_id = TM::getCurrentCompanyId();
                $consultant->ext = !empty($input['ext']) ? date('Y-m-d H:i:s', strtotime($input['ext'])) : null;
                $consultant->updated_at = date("Y-m-d H:i:s", time());
                $consultant->updated_by = TM::getCurrentUserId();
                $consultant->save();
            } else {
                $param = [
                    'user_id'    => $input['user_id'],
                    'title'      => array_get($input, 'title', null),
                    'company_id' => TM::getCurrentCompanyId(),
                    'ext'        => !empty($input['ext']) ? date('Y-m-d H:i:s', strtotime($input['ext'])) : null,
                ];
                $consultant = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $consultant;
    }

    public function getActiveConsultants($input = [], $limit)
    {
        $videoCallAccModel = new VideoCallAccountModel();
        $consultantsModel = new ConsultantModel();
        $result = $this->model->join($videoCallAccModel->getTable(), $videoCallAccModel->getTable() . '.user_id', '=', $consultantsModel->getTable() . '.user_id')
            ->where('is_online', 1)->whereNotNull('consultant_id');
        if ($limit) {
            if ($limit === 1) {
                return $result->first();
            } else {
                return $result->paginate($limit);
            }
        } else {
            return $result->get();
        }
    }
}