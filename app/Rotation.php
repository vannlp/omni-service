<?php


namespace App;


class Rotation extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'rotations';
    /**
     * @var string[]
     */
    protected $fillable
        = [
            'code',
            'name',
            'thumbnail_id',
            'description',
            'start_date',
            'end_date',
            'company_id',
            'is_active',
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
        public function condition()
        {
            return $this->hasMany(RotationCondition::class, 'rotation_id', 'id');
        }
        public function result()
        {
            return $this->hasMany(RotationResult::class, 'rotation_id', 'id');
        }
        public function thumbnail()
        {
            return $this->belongsTo(File::class, 'thumbnail_id', 'id');
        }
  
}