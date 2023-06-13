<?php

namespace App;

class PollQuestionAnswer extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'poll_question_answers';

    /**
     * @var array
     */
    protected $fillable = [
        'question_id',
        'text',
        'score',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by'
    ];

    public function question()
    {
        return $this->hasOne(__NAMESPACE__ . '\PollQuestion', 'question_id', 'id');
    }
}
