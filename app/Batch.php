<?php


namespace App;


class Batch extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'batches';

    protected $fillable = [
        'id',
        "code",
        'name',
        'company_id',
        "description",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}