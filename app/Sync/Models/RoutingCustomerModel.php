<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\RoutingCustomer;
use App\Supports\Message;
use App\TM;
use App\V1\Models\AbstractModel;

class RoutingCustomerModel extends AbstractModel
{
    public function __construct(RoutingCustomer $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $companyId                       = !empty($input['company_id']) ? $input['company_id'] : null;

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $routing_customer_model = RoutingCustomer::where('routing_customer_id', $id)->first();
            if (empty($routing_customer_model)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $routing_customer_model->routing_customer_id    = $input['routing_customer_id'] ?? $routing_customer_model['routing_customer_id'];
            $routing_customer_model->routing_id             = $input['routing_id'] ?? $routing_customer_model['routing_id'];
            $routing_customer_model->customer_id            = $input['customer_id'] ?? $routing_customer_model['customer_id'];
            $routing_customer_model->shop_id                = $input['shop_id'] ?? $routing_customer_model['shop_id'];
            $routing_customer_model->monday                 = $input['monday'] ?? $routing_customer_model['monday'];
            $routing_customer_model->tuesday                = $input['tuesday'] ?? $routing_customer_model['tuesday'];
            $routing_customer_model->wednesday              = $input['wednesday'] ?? $routing_customer_model['wednesday'];
            $routing_customer_model->thursday               = $input['thursday'] ?? $routing_customer_model['thursday'];
            $routing_customer_model->friday                 = $input['friday'] ?? $routing_customer_model['friday'];
            $routing_customer_model->saturday               = $input['saturday'] ?? $routing_customer_model['saturday'];
            $routing_customer_model->sunday                 = $input['sunday'] ?? $routing_customer_model['sunday'];
            $routing_customer_model->seq2                   = $input['seq2'] ?? $routing_customer_model['seq2'];
            $routing_customer_model->seq3                   = $input['seq3'] ?? $routing_customer_model['seq3'];
            $routing_customer_model->seq4                   = $input['seq4'] ?? $routing_customer_model['seq4'];
            $routing_customer_model->seq5                   = $input['seq5'] ?? $routing_customer_model['seq5'];
            $routing_customer_model->seq6                   = $input['seq6'] ?? $routing_customer_model['seq6'];
            $routing_customer_model->seq7                   = $input['seq7'] ?? $routing_customer_model['seq7'];
            $routing_customer_model->seq8                   = $input['seq8'] ?? $routing_customer_model['seq8'];
            $routing_customer_model->seq                    = $input['seq'] ?? $routing_customer_model['seq'];
            $routing_customer_model->start_week             = $input['start_week'] ?? $routing_customer_model['start_week'];
            $routing_customer_model->start_date             = $input['start_date'] ?? $routing_customer_model['start_date'];
            $routing_customer_model->end_date               = $input['end_date'] ?? $routing_customer_model['end_date'];
            $routing_customer_model->week1                  = $input['week1'] ?? $routing_customer_model['week1'];
            $routing_customer_model->week2                  = $input['week2'] ?? $routing_customer_model['week2'];
            $routing_customer_model->week3                  = $input['week3'] ?? $routing_customer_model['week3'];
            $routing_customer_model->week4                  = $input['week4'] ?? $routing_customer_model['week4'];
            $routing_customer_model->week_interval          = $input['week_interval'] ?? $routing_customer_model['week_interval'];
            $routing_customer_model->frequency              = $input['frequency'] ?? $routing_customer_model['frequency'];
            $routing_customer_model->last_order             = $input['last_order'] ?? $routing_customer_model['last_order'];
            $routing_customer_model->last_approve_order     = $input['last_approve_order'] ?? $routing_customer_model['last_approve_order'];
            $routing_customer_model->day_plan               = $input['day_plan'] ?? $routing_customer_model['day_plan'];
            $routing_customer_model->plan_date              = $input['plan_date'] ?? $routing_customer_model['plan_date'];
            $routing_customer_model->day_plan_avg           = $input['day_plan_avg'] ?? $routing_customer_model['day_plan_avg'];
            $routing_customer_model->plan_avg_date          = $input['plan_avg_date'] ?? $routing_customer_model['plan_avg_date'];
            $routing_customer_model->status                 = $input['status'] ?? $routing_customer_model['status'];
            $routing_customer_model->updated_at             = date("Y-m-d H:i:s", time());
            $routing_customer_model->updated_by             = TM::getIDP()->sync_name;
            $routing_customer_model->save();
        } else {
            $param = [
                'routing_customer_id' => array_get($input, 'routing_customer_id', NULL),
                'routing_id'          => array_get($input, 'routing_id', NULL),
                'customer_id'         => array_get($input, 'customer_id', NULL),
                'shop_id'             => array_get($input, 'shop_id', NULL),
                'monday'              => array_get($input, 'monday', NULL),
                'tuesday'             => array_get($input, 'tuesday', NULL),
                'wednesday'           => array_get($input, 'wednesday', NULL),
                'thursday'            => array_get($input, 'thursday', NULL),
                'friday'              => array_get($input, 'friday', NULL),
                'saturday'            => array_get($input, 'saturday', NULL),
                'sunday'              => array_get($input, 'sunday', NULL),
                'seq2'                => array_get($input, 'seq2', NULL),
                'seq3'                => array_get($input, 'seq3', NULL),
                'seq4'                => array_get($input, 'seq4', NULL),
                'seq5'                => array_get($input, 'seq5', NULL),
                'seq6'                => array_get($input, 'seq6', NULL),
                'seq7'                => array_get($input, 'seq7', NULL),
                'seq8'                => array_get($input, 'seq8', NULL),
                'seq'                 => array_get($input, 'seq', NULL),
                'start_week'          => array_get($input, 'start_week', NULL),
                'start_date'          => array_get($input, 'start_date', NULL),
                'end_date'            => array_get($input, 'end_date', NULL),
                'week1'               => array_get($input, 'week1', NULL),
                'week2'               => array_get($input, 'week2', NULL),
                'week3'               => array_get($input, 'week3', NULL),
                'week4'               => array_get($input, 'week4', NULL),
                'week_interval'       => array_get($input, 'week_interval', NULL),
                'frequency'           => array_get($input, 'frequency', NULL),
                'last_order'          => array_get($input, 'last_order', NULL),
                'last_approve_order'  => array_get($input, 'last_approve_order', NULL),
                'day_plan'            => array_get($input, 'day_plan', NULL),
                'plan_date'           => array_get($input, 'plan_date', NULL),
                'day_plan_avg'        => array_get($input, 'day_plan_avg', NULL),
                'plan_avg_date'       => array_get($input, 'plan_avg_date', NULL),
                'status'              => array_get($input, 'status', NULL),
                'created_at'          => array_get($input, 'created_at', NULL),
                'created_by'          => array_get($input, 'created_by', NULL),
                'updated_at'          => array_get($input, 'updated_at', NULL),
                'updated_by'          => array_get($input, 'updated_by', NULL)
            ];
            $routing_customer_model = $this->create($param);
        }
        return $routing_customer_model;
    }
}
