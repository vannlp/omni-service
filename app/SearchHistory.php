<?php


namespace App;


class SearchHistory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_histories';


    /**
     * @var array
     */
    protected $fillable = [
        'search_by',
        'keyword',
        'data',
        'store_ids',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}