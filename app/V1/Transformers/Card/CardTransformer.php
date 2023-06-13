<?php


namespace App\V1\Transformers\Card;


use App\Card;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CardTransformer extends TransformerAbstract
{
    public function transform(Card $card)
    {
        try {
            return [
                'id'         => $card->id,
                'code'       => $card->code,
                'name'       => $card->name,
                'from'       => date('d-m-Y', strtotime($card->from)),
                'expired'    => date('d-m-Y', strtotime($card->expired)),
                'type'       => $card->type,
                'is_active'  => $card->is_active,
                'created_at' => date('d-m-Y', strtotime($card->created_at)),
                'updated_at' => date('d-m-Y', strtotime($card->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}