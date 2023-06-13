<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\V1\Transformers\PromotionAds;

use App\PromotionAds;
use App\File;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PromotionAdsTransformer extends TransformerAbstract
{
    public function transform(PromotionAds $item)
    {
        try{
            $file_code = object_get($item, 'file.code');
            return [
                'id'            => $item->id,
                'image_id'      => $item->image_id,
                'image'         => !empty($file_code)?env('UPLOAD_URL').'/file/'. $file_code : null,
                'description'   => $item->description,
                'title'         => $item->title,
                'company_id'    => $item->company_id,
                'created_at'    => date('d-m-Y', strtotime($item->created_at)),
                'created_by'    => object_get($item, 'createdBy.profile.full_name'),
                'updated_at'    => date('d-m-Y', strtotime($item->updated_at)),
                'updated_by'    => object_get($item, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
