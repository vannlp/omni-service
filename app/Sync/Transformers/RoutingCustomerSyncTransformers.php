<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\RoutingCustomer;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class RoutingCustomerSyncTransformers extends TransformerAbstract
{
    public function transform(RoutingCustomer $routingCustomer)
    {
        try {
            return [
                'id'                        => $routingCustomer->id,
                'routing_customer_id'       => $routingCustomer->routing_customer_id,
                'routing_id'                => $routingCustomer->routing_id,
                'customer_id'               => $routingCustomer->customer_id,
                'shop_id'                   => $routingCustomer->shop_id,
                'monday'                    => $routingCustomer->monday,
                'tuesday'                   => $routingCustomer->tuesday,
                'wednesday'                 => $routingCustomer->wednesday,
                'thursday'                  => $routingCustomer->thursday,
                'friday'                    => $routingCustomer->friday,
                'saturday'                  => $routingCustomer->saturday,
                'sunday'                    => $routingCustomer->sunday,
                'seq2'                      => $routingCustomer->seq2,
                'seq3'                      => $routingCustomer->seq3,
                'seq4'                      => $routingCustomer->seq4,
                'seq5'                      => $routingCustomer->seq5,
                'seq6'                      => $routingCustomer->seq6,
                'seq7'                      => $routingCustomer->seq7,
                'seq8'                      => $routingCustomer->seq8,
                'seq'                       => $routingCustomer->seq,
                'start_week'                => $routingCustomer->start_week,
                'start_date'                => $routingCustomer->start_date,
                'end_date'                  => $routingCustomer->end_date,
                'week1'                     => $routingCustomer->week1,
                'week2'                     => $routingCustomer->week2,
                'week3'                     => $routingCustomer->week3,
                'week4'                     => $routingCustomer->week4,
                'week_interval'             => $routingCustomer->week_interval,
                'frequency'                 => $routingCustomer->frequency,
                'last_order'                => $routingCustomer->last_order,
                'last_approve_order'        => $routingCustomer->last_approve_order,
                'day_plan'                  => $routingCustomer->day_plan,
                'plan_date'                 => $routingCustomer->plan_date,
                'day_plan_avg'              => $routingCustomer->day_plan_avg,
                'plan_avg_date'             => $routingCustomer->plan_avg_date,
                'status'                    => $routingCustomer->status,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
