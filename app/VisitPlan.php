<?php

namespace App;

class VisitPlan extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'visit_plans';


    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'visit_plan_id',
        'routing_id',
        'shop_id',
        'staff_id',
        'from_date',
        'to_date',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}
