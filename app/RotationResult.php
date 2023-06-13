<?php


namespace App;


class RotationResult extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'rotation_results';
    /**
     * @var string[]
     */
    protected $fillable
    = [
        'rotation_id',
        'code',
        'name',
        'type',
        'coupon_id',
        'coupon_name',
        'description',
        'ratio',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];
    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'id', 'coupon_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function rotation()
    {
        return $this->hasOne(__NAMESPACE__ . '\Rotation', 'id', 'rotation_id');
    }
}
