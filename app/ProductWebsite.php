<?php


namespace App;


class ProductWebsite extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_websites';


    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'website_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function website()
    {
        return $this->hasOne(__NAMESPACE__ . '\Website', 'id', 'website_id');
    }

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }
}