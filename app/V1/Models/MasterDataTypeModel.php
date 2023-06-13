<?php
/**
 * Date: 2/23/2019
 * Time: 1:50 PM
 */

namespace App\V1\Models;


use App\MasterDataType;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;

class MasterDataTypeModel extends AbstractModel
{
    public function __construct(MasterDataType $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            $companyId = TM::getCurrentCompanyId();
            if ($id) {
                $masterDataType = MasterDataType::find($id);
                if (empty($masterDataType)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $masterDataType->type = array_get($input, 'type', $masterDataType->type);
                $masterDataType->name = array_get($input, 'name', $masterDataType->name);
                $masterDataType->description = array_get($input, 'description', $masterDataType->name);
                $masterDataType->data = array_get($input, 'data', $masterDataType->data);
                $masterDataType->status = array_get($input, 'status', 1);
                $masterDataType->sort = array_get($input, 'sort', $masterDataType->sort);
                $masterDataType->company_id = $companyId;
                $masterDataType->updated_at = date("Y-m-d H:i:s", time());
                $masterDataType->updated_by = TM::getCurrentUserId();
                $masterDataType->save();
            } else {
                $param = [
                    'type'        => array_get($input, 'type'),
                    'name'        => array_get($input, 'name'),
                    'description' => array_get($input, 'description'),
                    'data'        => array_get($input, 'data'),
                    'status'      => array_get($input, 'status', 1),
                    'sort'        => array_get($input, 'sort'),
                    'company_id'  => $companyId,
                ];
                $masterDataType = $this->create($param);
            }
            return $masterDataType;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }


    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $companyId = self::getCurrentCompany();
        $query = $query->where('company_id', $companyId);
        foreach ($input as $column => $value) {
            if (in_array($column,
                    ['type', 'name', 'status']) && !empty($value)) {
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