<?php


namespace App;


class PostSearchHistory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_search_histories';


    /**
     * @var array
     */
    protected $fillable = [
        'search_by',
        'keyword',
        'website_id',
        'company_id',
        'result',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}