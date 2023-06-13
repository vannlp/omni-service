<?php

namespace App\V1\Models;

use App\ProductAttribute;
use App\TM;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductAttributeModel extends AbstractModel {
    /**
     * ProductAttributeModel constructor.
     *
     * @param ProductAttribute|null $model
     */
    public function __construct(ProductAttribute $model = null) {
        parent::__construct($model);
    }

    /**
     * Get list by product ID
     *
     * @param $productId
     * @param bool $onlyIsActive
     * @return array
     */
    public function getListByProductId($productId, $onlyIsActive = false) {
        $query = $this->make([
                'attribute' => function ($query) {
                    $query->select(['id', 'name', 'attribute_group_id', 'value'])
                            ->with('attributeGroup:id,name,type');
                }
        ]);

        if ($onlyIsActive == true) {
            $query = $query->where('is_active', 1);
        }

        $productAttributes = $query->where('product_id', $productId)->get();

        $results = [];

        if (!$productAttributes->isEmpty()) {
            foreach ($productAttributes as $productAttribute) {
                if (empty(Arr::get($productAttribute, 'attribute.attributeGroup', null))) {
                    continue;
                }
                $results[$productAttribute->attribute->attributeGroup->id]['attribute_group'] = $productAttribute->attribute->attributeGroup;
                $results[$productAttribute->attribute->attributeGroup->id]['is_active']       = $productAttribute->is_active;
                $results[$productAttribute->attribute->attributeGroup->id]['details'][]       = [
                        'product_attribute_id' => $productAttribute->id,
                        'id'                   => $productAttribute->attribute->id,
                        'name'                 => $productAttribute->attribute->name,
                        'value'                => $productAttribute->attribute->value
                ];
            }
        }

        return array_values($results);
    }

    /**
     * Sync data
     *
     * @param array $input
     * @return bool|mixed
     * @throws \Exception
     */
    public function syncData(array $input) {
        $productId        = $input['product_id'];
        $attributes       = $input['attributes'];
        $attributeGroupId = $input['attribute_group_id'];
        $isActive         = !empty($input['is_active']) ? 1 : 0;
        try {
            DB::beginTransaction();

            $isProductAttributeIds = $this->model
                    ->whereHas('attribute.attributeGroup', function ($query) use ($attributeGroupId) {
                        $query->where('id', $attributeGroupId);
                    })
                    ->where('product_id', $productId)
                    ->get()
                    ->pluck('attribute_id')->toArray();

            if (!empty($isProductAttributeIds)) {
                $arrDiff = array_diff($isProductAttributeIds, $attributes);

                if (!empty($arrDiff)) {
                    $this->model
                            ->where('product_id', $productId)
                            ->whereIn('attribute_id', $arrDiff)
                            ->update([
                                    'deleted_by' => TM::getCurrentUserId(),
                                    'deleted'    => 1,
                                    'deleted_at' => Carbon::now(),
                                    'is_active'  => $isActive
                            ]);
                }
            }

            $productAttributeRestore = [];
            foreach ($attributes as $attributeId) {
                $isProductAttribute = $this->model
                        ->where('product_id', $productId)
                        ->where('attribute_id', $attributeId)
                        ->withTrashed()
                        ->first();

                if (empty($isProductAttribute)) {
                    $this->model->create([
                            'product_id'   => $productId,
                            'attribute_id' => $attributeId,
                            'is_active'    => $isActive
                    ]);
                } elseif ($isProductAttribute->deleted == 1) {
                    $productAttributeRestore[] = $isProductAttribute->id;
                } else {
                    $this->model->where('product_id', $productId)
                            ->where('attribute_id', $attributeId)
                            ->update(['is_active' => $isActive]);
                }
            }

            if (!empty($productAttributeRestore)) {
                DB::table('product_attributes')
                        ->whereIn('id', $productAttributeRestore)
                        ->update([
                                'is_active'  => $isActive,
                                'deleted_by' => null,
                                'deleted'    => 0,
                                'deleted_at' => null
                        ]);
            }

            DB::commit();

            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

}