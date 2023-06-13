<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 21:48
 */

namespace App;


class Company extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    protected $fillable
        = [
            'code',
            'name',
            'email',
            'address',
            'tax_code',
            'phone',
            'firebase_token',
            'company_key',
            'email_notification',
            'description',
            'avatar_id',
            'avatar',
            'verify_token',
            'is_active',
            'deleted',
            'created_at',
            'created_by',
            'updated_by',
            'updated_at',
        ];

    protected $hidden
        = [
            'verify_token',
        ];

    public function createdBy()
    {
        return $this->hasOne(Profile::class, 'user_id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(Profile::class, 'user_id', 'updated_by');
    }

    public function userGroup()
    {
        return $this->belongsTo(UserGroup::class, 'id', 'company_id');
    }

    public function userGroupDefault()
    {
        return $this->belongsTo(UserGroup::class, 'id', 'company_id')
            ->where('is_default', 1);
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'avatar_id');
    }
}
