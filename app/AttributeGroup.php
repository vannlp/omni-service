<?php

namespace App;

class AttributeGroup extends BaseModel
{
    /**
     * $type
     */
    const TYPE = ['COLOR', 'IMAGE', 'LABEL'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attribute_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "store_id",
        "type",
        "name",
        "description",
        "slug",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by"
    ];

    /**
     * Belongs to store
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}