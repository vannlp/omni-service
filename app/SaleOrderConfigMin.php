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
class SaleOrderConfigMin extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'sale_order_config_mins';


    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'shop_id',
        'from_date',
        'to_date',
        'product_id',
        'unit_id',
        'quantity',
        'status',
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
    public function shop()
    {
        return $this->hasOne(ShopSync::class, 'id', 'unit_id');
    }
}