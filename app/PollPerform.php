<?php

namespace App;

class PollPerform extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'poll_perform';

    /**
     * @var array
     */
    protected $fillable = [
        'poll_id',
        'object_code',
        'object_name',
        'score',
        'details_json',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by'
    ];
}
