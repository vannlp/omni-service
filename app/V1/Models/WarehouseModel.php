<?php


namespace App\V1\Models;


use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\Warehouse;

class WarehouseModel extends AbstractModel
{
    /**
     * WarehouseModel constructor.
     * @param Warehouse|null $model
     */
    public function __construct(Warehouse $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            $this->checkUnique(['name' => $input['name'],], $id);
            if ($id) {
                $warehouse = Warehouse::find($id);
                if (empty($warehouse)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $warehouse->name = array_get($input, 'name', $warehouse->name);
                $warehouse->code = array_get($input, 'code', $warehouse->code);
                $warehouse->address = array_get($input, 'address', $warehouse->address);
                $warehouse->shop_id = array_get($input, 'shop_id', $warehouse->shop_id);
                $warehouse->description = array_get($input, 'description', NULL);
                $warehouse->company_id = TM::getCurrentCompanyId();
                $warehouse->updated_at = date("Y-m-d H:i:s", time());
                $warehouse->updated_by = TM::getCurrentUserId();
                $warehouse->save();
            } else {
                $param = [
                    'code'        => $input['code'],
                    'name'        => $input['name'],
                    'address'     => array_get($input, 'address'),
                    'shop_id'     => array_get($input, 'shop_id'),
                    'description' => array_get($input, 'description'),
                    'company_id'  => TM::getCurrentCompanyId(),
                    'is_active'   => 1,
                ];
                $warehouse = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $warehouse;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $current_company_id = TM::getCurrentCompanyId();
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        $query->where('company_id',$current_company_id);
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