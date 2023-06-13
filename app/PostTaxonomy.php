<?php


namespace App;


class PostTaxonomy extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'post_taxonomies';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'post_id',
        'taxonomy_id',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function getTaxonomy()
    {
        return $this->hasOne(Taxonomy::class, 'id', 'taxonomy_id');
    }

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
}