<?php


namespace App\V1\Models;


use App\Batch;
use App\Supports\Message;
use App\TM;

class BatchModel extends AbstractModel
{
    /**
     * CityModel constructor.
     * @param Batch|null $model
     */
    public function __construct(Batch $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $batch = Batch::find($id);
            if (empty($batch)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $batch->name = array_get($input, 'name', $batch->name);
            $batch->code = array_get($input, 'code', $batch->code);
            $batch->company_id = TM::getCurrentCompanyId();
            $batch->description = array_get($input, 'description', NULL);
            $batch->save();
        } else {
            $param = [
                'name' => $input['name'],
                'code' => $input['code'],
                'company_id' => TM::getCurrentCompanyId(),
                'description' => array_get($input, 'description')
            ];
            $batch = $this->create($param);
        }
        return $batch;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        $query->where('company_id', TM::getCurrentCompanyId());
        if (!empty($input['created_at'])) {
            $date = $input['created_at'];
            $input['created_at'] = ['like' => date('Y-m-d', strtotime($date))];
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
}