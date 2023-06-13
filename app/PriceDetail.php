<?php


namespace App;


class PriceDetail extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'price_details';

    /**
     * @var string[]
     */
    protected $fillable = [
        'price_id',
        'product_id',
        'from',
        'to',
        'price',
        'status',
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function price()
    {
        return $this->belongsTo(Price::class, 'price_id', 'id');
    }
}