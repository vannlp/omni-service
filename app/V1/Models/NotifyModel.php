<?php

namespace App\V1\Models;


use App\Notify;
use App\Supports\Message;
use App\TM;
use App\V1\Models\AbstractModel;

class NotifyModel extends AbstractModel
{
    /**
     * NotifyModel constructor.
     * @param Notify|null $model
     */
    public function __construct(Notify $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (TM::getCurrentRole() != USER_ROLE_ADMIN) {
            $query->where(function ($qr) {
                $qr->where('notify_for', 'ALL')->orWhere(function ($q) {
                    $q->where('user_id', TM::getCurrentUserId())->orWhere('created_by', TM::getCurrentUserId());
                });
            });
        }
        $query->where('company_id', TM::getCurrentCompanyId());
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $notify = Notify::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($notify)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $notify->title = array_get($input, 'title', $notify->title);
            $notify->body = $input['body'];
            $notify->type = array_get($input, 'type', $notify->type);
            $notify->target_id = array_get($input, 'target_id', $notify->target_id);
            $notify->product_search_query = array_get($input, 'product_search_query', $notify->product_search_query);
            $notify->notify_for = array_get($input, 'notify_for', $notify->notify_for);
            $notify->delivery_date = !empty($input['delivery_date']) ? date("Y-m-d H:i:s",
                strtotime($input['delivery_date'])) : $notify->delivery_date;
            $notify->frequency = array_get($input, 'frequency', $notify->frequency);
            $notify->user_id = array_get($input, 'user_id', $notify->user_id);
            $notify->company_id = TM::getCurrentCompanyId();
            $notify->updated_at = date("Y-m-d H:i:s", time());
            $notify->updated_by = TM::getCurrentUserId();
            $notify->save();
        } else {
            $param = [
                'title'                => $input['title'],
                'body'                 => $input['body'],
                'type'                 => $input['type'],
                'target_id'            => $input['target_id'] ?? null,
                'product_search_query' => $input['product_search_query'] ?? null,
                'notify_for'           => $input['notify_for'],
                'delivery_date'        => !empty($input['delivery_date']) ? date("Y-m-d H:i:s",
                    strtotime($input['delivery_date'])) : null,
                'frequency'            => $input['frequency'],
                'user_id'              => array_get($input, 'user_id', null),
                'company_id'           => TM::getCurrentCompanyId(),
            ];
            $notify = $this->create($param);
        }

        return $notify;
    }
}

/*
 * ALTER TABLE `tm_dev`.`promotions`  CHANGE COLUMN `description` `description` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL;
 */