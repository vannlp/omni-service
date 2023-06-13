<?php


namespace App\V1\Transformers\Rotation;


use App\Rotation;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class RotationTransformer extends TransformerAbstract
{
    public function transform(Rotation $rotation)
    {
        try {
            return [
                'id'                => $rotation->id,
                'code'              => $rotation->code,
                'name'              => $rotation->name,
                'thumbnail_id'      => $rotation->thumbnail_id,
                'thumbnail'         => !empty($rotation->thumbnail_id) ? env('GET_FILE_URL').$rotation->thumbnail->code : null,
                'name'              => $rotation->name,
                'description'       => $rotation->description,
                'start_date'        => $rotation->start_date,
                'end_date'          => $rotation->end_date,
                'company_id'        => $rotation->company_id,
                'is_active'         => $rotation->is_active,
                'conditions'        => object_get($rotation, 'condition'),
                'results'           => object_get($rotation, 'result'),
                'created_at'        => date('d-m-Y', strtotime($rotation->created_at)),
                'updated_at'        => date('d-m-Y', strtotime($rotation->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}