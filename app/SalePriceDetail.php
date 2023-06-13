<?php


namespace App;


class SalePriceDetail extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'sale_price_details';


    /**
     * @var array
     */
    protected $fillable = [
        'sale_price_id',
        'customer_group_ids',
        'company_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
    public function customerGroup()
    {
        return $this->hasOne(__NAMESPACE__ . '\UserGroup', 'id', 'customer_group_ids');
    }
}