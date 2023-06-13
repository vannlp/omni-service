<?php
/**
 * Created by PhpStorm.
 * User: SaoBang
 * Date: 9/14/2019
 * Time: 20:25
 */

namespace App;


class Unit extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'units';

    protected $fillable = [
        'code',
        'name',
        'description',
        'order',
        'company_id',
        'store_id',
        'is_active',
        'deleted',
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }
}