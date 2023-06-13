<?php
/**
 * Date: 2/23/2019
 * Time: 4:13 PM
 */

namespace App\V1\Transformers\MasterDataType;


use App\MasterDataType;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

/**
 * Class MasterDataTypeTransformer
 *
 * @package App\V1\CMS\Transformers\MasterDataType
 */
class MasterDataTypeTransformer extends TransformerAbstract
{
    public function transform(MasterDataType $masterDataType)
    {
        try {
            return [
                'id'          => $masterDataType->id,
                'type'        => $masterDataType->type,
                'name'        => $masterDataType->name,
                'description' => $masterDataType->description,
                'data'        => $masterDataType->data,
                'status'      => $masterDataType->status,
                'sort'        => $masterDataType->sort,
                'company_id'  => $masterDataType->company_id,
                'created_at'  => date('d-m-Y', strtotime($masterDataType->created_at)),
                'created_by'  => object_get($masterDataType, 'createdBy.profile.full_name'),
                'updated_at'  => !empty($masterDataType->updated_at) ? date('d-m-Y', strtotime($masterDataType->updated_at)) : null,
                'updated_by'  => object_get($masterDataType, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['type']);
        }
    }
}