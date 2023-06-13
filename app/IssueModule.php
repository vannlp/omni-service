<?php


namespace App;


class IssueModule extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'issue_modules';

    /**
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'company_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}