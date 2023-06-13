<?php


namespace App;


class Promocode extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'promocodes';
    /**
     * @var string[]
     */
    protected $fillable
        = [
            'code',
            'value',
            'user_use',
            'is_active',
            'deleted',
            'deleted_at',
            'deleted_by',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
        ];

}