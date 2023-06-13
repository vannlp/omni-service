<?php
/**
 * User: dai.ho
 * Date: 1/06/2020
 * Time: 1:26 PM
 */

namespace App;


class CatalogOption extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalog_options';

    protected $fillable = [
        'code',
        'name',
        'type',
        'order',
        'description',
        'store_id',
        'company_id',
        'values',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}