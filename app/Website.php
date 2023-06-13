<?php


namespace App;


class Website extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'websites';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'domain',
        'theme_data',
        'logo',
        'favico',
        'description',
        'keyword',
        'blog_id',
        'company_id',
        'store_id',
        'status',
        'facebook_id',
        'google_analytic_id',
        'facebook_analytics',
        'google_analytics',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function getBlog()
    {
        return $this->hasOne(Blog::class, 'id', 'blog_id');
    }

    public function getStore()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

}