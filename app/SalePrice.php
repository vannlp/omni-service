<?php
/**
 * Created by PhpStorm.
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 3:12 PM
 */

namespace App;

/**
 * Class SalePrice
 * @package App
 */
class SalePrice extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'sale_prices';


    /**
     * @var array
     */
    protected $fillable = [
        'price',
        'discount',
        'description',
        'product_id',
        'product_code',
        'product_name',
        'company_id',
        'unit_id',
        'unit_code',
        'unit_name',
        'price_id',
        'price_code',
        'price_name',
        'customer_group_ids',
        'cs_number',
        'seed_level',
        'packing_standard',
        'from',
        'to',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }

    public function units()
    {
        return $this->hasOne(__NAMESPACE__ . '\Unit', 'id', 'unit_id');
    }

    public function groups()
    {
        return $this->hasOne(__NAMESPACE__ . '\UserGroup', 'id', 'customer_group_ids');
    }

    public function types()
    {
        return $this->hasOne(__NAMESPACE__ . '\Price', 'id', 'price_id');
    }

    public function salePriceDetails()
    {
        return $this->hasMany(__NAMESPACE__ . '\SalePriceDetail', 'sale_price_id', 'id');
    }
}