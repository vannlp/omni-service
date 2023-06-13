<?php
namespace App\V1\Models;


use App\Supports\Message;
use App\TM;
use App\ReasonCancel;

class ReasonCancelModel extends AbstractModel
{
    public function __construct(ReasonCancel $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return ReasonCancel|mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            /** @var ReasonCancel $item */
            $item = ReasonCancel::model()->where('id', $id)->first();
            // print_r($item);die;
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->code            = $input['code'] ?? null;
            $item->is_choose       = $input['is_choose'] ?? 0;
            $item->value           = $input['value'];
            $item->type            = $input['type'];
            $item->group_reason    = $input['group_reason'] ?? null;
            $item->is_description  = $input['is_description'] ?? 0;
            $item->company_id      = TM::getCurrentCompanyId();
            $item->save();
        } else {
            $param = [
                'code'            => $input['code'] ?? null,
                'is_choose'       => $input['is_choose'] ?? 0,
                'value'           => $input['value'],
                'type'            => $input['type'],
                'group_reason'    => $input['group_reason'] ?? null,
                'is_description'  => $input['is_description'] ?? 0,
                'company_id'      => TM::getCurrentCompanyId()
            ];
            $item = $this->create($param);
        }
        return $item;
    }
}