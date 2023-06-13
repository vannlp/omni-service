<?php

namespace App\V1\Models;


use App\MasterData;
use App\MasterDataType;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;

class MasterDataModel extends AbstractModel
{
    /**
     * MasterDataModel constructor.
     *
     * @param MasterData|null $model
     */
    public function __construct(MasterData $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            $companyId = TM::getCurrentCompanyId();
            if ($id) {
                $masterData = MasterData::find($id);
                if (empty($masterData)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $masterData->name = array_get($input, 'name', $masterData->name);
                $masterData->status = array_get($input, 'status', 1);
                $masterData->type = array_get($input, 'type', $masterData->type);
                $masterData->description = array_get($input, 'description', $masterData->description);
                $masterData->data = array_get($input, 'data', $masterData->data);
                $masterData->store_id = array_get($input, 'store_id', $masterData->store_id);
                $masterData->company_id = $companyId;
                $masterData->updated_at = date("Y-m-d H:i:s", time());
                $masterData->updated_by = TM::getCurrentUserId();
                $masterData->save();
            } else {
                $param = [
                    'code'        => array_get($input, 'code'),
                    'name'        => array_get($input, 'name'),
                    'status'      => array_get($input, 'status', 1),
                    'type'        => array_get($input, 'type'),
                    'description' => array_get($input, 'description'),
                    'data'        => array_get($input, 'data'),
                    'type_id'     => array_get($input, 'type_id'),
                    'sort'        => array_get($input, 'sort'),
                    'store_id'    => array_get($input, 'store_id', null),
                    'company_id'  => $companyId,
                ];
                $masterData = $this->create($param);
            }
            return $masterData;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $companyId = TM::getCurrentCompanyId();
        $query = $query->where('company_id', $companyId);
        foreach ($input as $column => $value) {
            if (in_array($column,
                    ['code', 'name', 'status', 'type']) && !empty($value)) {
                $query = $query->where($column, 'like', "%$value%");
            }
        }
        $query = $query->orderBy('sort', 'asc');
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

    public function searchNotLogin($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        foreach ($input as $column => $value) {
            if (in_array($column,
                    ['code', 'name', 'status', 'type']) && !empty($value)) {
                $query = $query->where($column, 'like', "%$value%");
            }
        }
        $query = $query->orderBy('sort', 'asc');
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