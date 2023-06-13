<?php


namespace App\V1\Models;


use App\Card;
use App\Supports\Message;
use App\TM;

class CardModel extends AbstractModel
{
    public function __construct(Card $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $card = Card::find($id);
            if (empty($card)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $card->code = array_get($input, 'code', $card->code);
            $card->name = array_get($input, 'name', $card->name);
            $card->from = array_get($input, 'from', $card->from);
            $card->expired = array_get($input, 'expired', $card->expired);
            $card->type = array_get($input, 'type', $card->type);
            $card->updated_at = date("Y-m-d H:i:s", time());
            $card->updated_by = TM::getCurrentUserId();
            $card->save();
        } else {
            $param = [
                'name'      => $input['name'],
                'code'      => $input['code'],
                'from'      => array_get($input, 'from', null),
                'expired'   => array_get($input, 'expired', null),
                'type'      => $input['type'],
                'is_active' => 1,

            ];
            $card = $this->create($param);
        }

        return $card;
    }
}