<?php


namespace App\V1\Models;


use App\TM;
use App\WarehouseDetail;

class WarehouseDetailModel extends AbstractModel
{
    /**
     * CategoryModel constructor.
     * @param WarehouseDetail|null $model
     */
    public function __construct(WarehouseDetail $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);

        if (!empty($input['product_name'])) {
            $query->where('product_name', 'like', "%{$input['product_name']}%");
        }

        if (!empty($input['product_code'])) {
            $query->where('product_code', 'like', "%{$input['product_code']}%");
        }

        if (!empty($input['batch_name'])) {
            $query->where('batch_name', 'like', "%{$input['batch_name']}%");
        }

        if (!empty($input['batch_code'])) {
            $query->where('batch_code', 'like', "%{$input['batch_code']}%");
        }

        if (!empty($input['warehouse_id'])) {
            $query->where('warehouse_id', $input['warehouse_id']);
        }

        if (!empty($input['warehouse_code'])) {
            $query->where('warehouse_code', 'like', "%{$input['warehouse_code']}%");
        }

        if (!empty($input['warehouse_name'])) {
            $query->where('warehouse_name', 'like', "%{$input['warehouse_name']}%");
        }

        if (!empty($input['unit_code'])) {
            $query->where('unit_code', 'like', "%{$input['unit_code']}%");
        }

        if(!empty($input['unit_name'])){
            $query->where('unit_name', 'like', "%{$input['unit_name']}%");
        }

        $query->where('company_id', TM::getCurrentCompanyId());
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