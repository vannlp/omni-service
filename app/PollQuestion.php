<?php

namespace App;

class PollQuestion extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'poll_questions';

    /**
     * @var array
     */
    protected $fillable = [
        'poll_id',
        'title',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by'
    ];

    public function answers()
    {
        return $this->hasMany(__NAMESPACE__ . '\PollQuestionAnswer', 'question_id', 'id');
    }
}
