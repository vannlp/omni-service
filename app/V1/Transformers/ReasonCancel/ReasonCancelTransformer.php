<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:21
 */

namespace App\V1\Transformers\ReasonCancel;

use App\ReasonCancel;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ReasonCancelTransformer extends TransformerAbstract
{
    /**
     * @param ReasonCancel $item
     * @return array
     * @throws \Exception
     */
    public function transform(ReasonCancel $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'is_choose'   => $item->is_choose,
                'value'       => $item->value,
                'group_reason'=> $item->group_reason,
                'is_description' => $item->is_description,
                'type'        => $item->type,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['value']);
        }
    }
}
