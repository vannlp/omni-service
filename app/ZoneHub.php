<?php


namespace App;


class ZoneHub extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'zone_hubs';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'latlong',
        'description',
        'company_id',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by'
    ];

    public function user()
    {
        return $this->belongsToMany(User::class, 'user_has_zone_hubs');
    }
}