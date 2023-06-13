<?php
/**
 * User: dai.ho
 * Date: 8/06/2020
 * Time: 2:38 PM
 */

namespace App;


class ShippingMethod extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_methods';

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'company_id',
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

    public function avatar()
    {
        return $this->hasOne(File::class, 'id', 'avatar_id');
    }
}
