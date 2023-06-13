<?php

namespace App;

class PromotionAds extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotion_ads';

    protected $fillable = [
        'title',
        'description',
        'company_id',
        'image_id',
        'deleted',
        'coupon',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
        'deleted_by',
        'deleted_at',
    ];

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
}