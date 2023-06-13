<?php


namespace App;


class RotationDetail extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'rotation_details';
    /**
     * @var array
     */
    protected $fillable
        = [
            'user_id',
            'rotation_code',
            'deleted',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
            'deleted_at',
            'deleted_by',
        ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function rotationResult()
    {
        return $this->hasOne(RotationResult::class, 'code', 'rotation_code');
    }
}