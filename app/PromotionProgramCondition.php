<?php


namespace App;


class PromotionProgramCondition extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotion_program_conditions';

    protected $fillable = [
        'promotion_program_id',
        'condition_name',
        'condition_type',
        'item_id',
        'item_code',
        'item_name',
        'condition_type_name',
        'multiply_type',
        'condition_include_parent',
        'condition_include_child',
        'condition_input',
        'condition_limit',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];
}