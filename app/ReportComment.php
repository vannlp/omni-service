<?php


namespace App;


class ReportComment extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report_comments';


    /**
     * @var array
     */
    protected $fillable = [
        'comment_id',
        'user_id',
        'user_name',
        'content',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function postComments()
    {
        return $this->hasOne(__NAMESPACE__ . '\PostComment', 'id', 'comment_id');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }
}