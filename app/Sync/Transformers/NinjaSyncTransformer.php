<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;
use App\NinjaSync;
use App\File;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class NinjaSyncTransformer extends TransformerAbstract
{
       public function transform(NinjaSync $ninjaSync)
       {
              try {
                     return [
                            'id'=>$ninjaSync->id,
                            'name'            => $ninjaSync->name,
                            'name_post'      => $ninjaSync->name_post,
                            'reaction'   => $ninjaSync->reaction,
                            'comment'         => $ninjaSync->comment,
                            'company_id'    => $ninjaSync->company_id,
                            'share'    => $ninjaSync->share,
                            // 'created_at'    => date('d-m-Y', strtotime($ninjaSync->created_at)),
                            // 'created_by'    => object_get($ninjaSync, 'createdBy.profile.full_name'),
                            // 'updated_at'    => date('d-m-Y', strtotime($ninjaSync->updated_at)),
                            // 'updated_by'    => object_get($ninjaSync, 'updatedBy.profile.full_name'),
                     ];
              } catch (\Exception $ex) {
                     $response = TM_Error::handle($ex);
                     throw new \Exception($response['message'], $response['code']);
              }
       }
}
