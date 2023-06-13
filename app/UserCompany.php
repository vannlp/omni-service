<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 22:53
 */

namespace App;


class UserCompany extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_companies';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'company_id',
        'role_id',
        'company_code',
        'company_name',
        'company_key',
        'user_code',
        'user_name',
        'role_code',
        'role_name',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
}