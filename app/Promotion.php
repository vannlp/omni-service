<?php


namespace App;


class Promotion extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotions';

    protected $fillable = [
        'code',
        'title',
        'from',
        'to',
        'discount_rate',
        'max_discount',
        'condition_ids',
        'description',
        'type',
        'point',
        'ranking_id',
        'image_id',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    public function details()
    {
        return $this->hasMany(__NAMESPACE__ . '\PromotionDetail', 'promotion_id', 'id');
    }

    public function image()
    {
        return $this->hasOne(__NAMESPACE__ . '\Image', 'id', 'image_id');
    }

    public function file()
    {
        return $this->hasOne(__NAMESPACE__ . '\File', 'id', 'image_id');
    }

}