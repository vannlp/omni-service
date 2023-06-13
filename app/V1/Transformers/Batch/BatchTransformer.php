<?php


namespace App\V1\Transformers\Batch;


use App\Batch;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class BatchTransformer extends TransformerAbstract
{
    /**
     * @param Batch $batch
     * @return array
     * @throws \Exception
     */
    public function transform(Batch $batch)
    {
        try {
            return [
                'id'                 => $batch->id,
                'code'               => $batch->code,
                'name'               => $batch->name,
                'description'        => $batch->description,
                'created_at'         => date('d-m-Y', strtotime($batch->created_at)),
                'updated_at'         => date('d-m-Y', strtotime($batch->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}