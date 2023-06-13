<?php


namespace App;


class UserLocation extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_locations';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'lat',
        'long',
        'login_at',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];
}