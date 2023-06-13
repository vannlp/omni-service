<?php
/**
 * User: kpistech2
 * Date: 2020-07-04
 * Time: 00:42
 */

namespace App;


class Setting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    protected $fillable = [
        'code',
        'slug',
        'name',
        'value',
        'description',
        'type',
        'publish',
        'data',
        'data_client',
        'categories',
        'store_id',
        'company_id',
        'data_cke',
        'data_first',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    // protected $casts
    // = [
    //     'data' => 'json',
    //     'data_client' => 'json'
    // ];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}