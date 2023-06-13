<?php


namespace App;


class RotationCondition extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'rotation_conditions';
    /**
     * @var array
     */
    protected $fillable
        = [
            'rotation_id',
            'name',
            'code',
            'type',
            'price',
            'is_active',
            'deleted',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
            'deleted_at',
            'deleted_by',
        ];

    public function rotation()
    {
        return $this->hasOne(Rotation::class, 'id', 'rotation_id');
    }
}