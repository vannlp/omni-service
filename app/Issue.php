<?php

namespace App;

/**
 * Class Role
 *
 * @package App
 */
class Issue extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'issues';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'module_category_id',
        'estimated_time',
        'user_id',
        'company_id',
        'parent_id',
        'progress',
        'priority',
        'start_time',
        'deadline',
        'version',
        'file_id',
        'is_prompt',
        'related_issues',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function moduleCategory()
    {
        return $this->hasOne(IssueModuleCategory::class, 'id', 'module_category_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function created_By()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function updated_By()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

//    public function log_wordIssue()
//    {
//        return $this->hasMany(__NAMESPACE__ . '\LogWord', 'issue_id', 'id');
//    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
}
