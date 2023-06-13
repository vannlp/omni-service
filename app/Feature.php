<?php
/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:43
 */

namespace App;


class Feature extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'features';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}
