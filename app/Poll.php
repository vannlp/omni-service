<?php

namespace App;

class Poll extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'polls';

    /**
     * @var array
     */
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by'
    ];

    public function questions()
    {
        return $this->hasMany(__NAMESPACE__ . '\PollQuestion', 'poll_id', 'id');
    }

    public function performers()
    {
        return $this->hasMany(__NAMESPACE__ . '\PollPerform', 'poll_id', 'id');
    }
}
