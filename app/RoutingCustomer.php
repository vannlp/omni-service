<?php

namespace App;

class RoutingCustomer extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'routing_customers';


    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'routing_customer_id',
        'routing_id',
        'customer_id',
        'shop_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'seq2',
        'seq3',
        'seq4',
        'seq5',
        'seq6',
        'seq7',
        'seq8',
        'seq',
        'start_week',
        'start_date',
        'end_date',
        'week1',
        'week2',
        'week3',
        'week4',
        'week_interval',
        'frequency',
        'last_order',
        'last_approve_order',
        'day_plan',
        'plan_date',
        'day_plan_avg',
        'plan_avg_date',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}
