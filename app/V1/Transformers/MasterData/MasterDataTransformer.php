<?php
/**
 * Date: 2/21/2019
 * Time: 1:57 PM
 */

namespace App\V1\Transformers\MasterData;


use App\MasterData;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

/**
 * Class MasterDataTransformer
 *
 * @package App\V1\CMS\Transformers\MasterData
 */
class MasterDataTransformer extends TransformerAbstract
{
    public function transform(MasterData $masterData)
    {
        try {
            return [
                'id'          => $masterData->id,
                'code'        => $masterData->code,
                'name'        => $masterData->name,
                'status'      => $masterData->status,
                'type'        => $masterData->type,
                'description' => $masterData->description,
                'sort'        => $masterData->sort,
                'data'        => $masterData->data,
                'company_id'  => $masterData->company_id,
                'created_at'  => date('d-m-Y', strtotime($masterData->created_at)),
                'created_by'  => object_get($masterData, 'createdBy.profile.full_name'),
                'updated_at'  => !empty($masterData->updated_at) ? date('d-m-Y', strtotime($masterData->updated_at)) : null,
                'updated_by'  => object_get($masterData, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['status']);
        }
    }
}
