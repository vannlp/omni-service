<?php

namespace App;

class Attribute extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attributes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "attribute_group_id",
        "name",
        "description",
        "value",
        "slug",
        "order",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by"
    ];

    /**
     * Belongs to attribute group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attributeGroup()
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id', 'id');
    }
}