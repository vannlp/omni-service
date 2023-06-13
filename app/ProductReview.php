<?php


namespace App;


/**
 * Class ProductReview
 * @package App
 */
class ProductReview extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'product_reviews';
    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id',
        'rate',
        'message',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }
}