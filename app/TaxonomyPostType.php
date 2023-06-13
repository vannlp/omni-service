<?php


namespace App;


class TaxonomyPostType extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'taxonomy_post_types';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'post_type_id',
        'taxonomy_id',
        'is_active',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function postType()
    {
        return $this->hasOne(PostType::class, 'id', 'post_type_id');
    }

    public function taxonomy()
    {
        return $this->hasOne(Taxonomy::class, 'id', 'taxonomy_id');
    }
}