<?php


namespace App;


class IssueModuleCategory extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'issue_module_categories';

    /**
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'company_id',
        'module_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function module()
    {
        return $this->hasOne(IssueModule::class, 'id', 'module_id');
    }
}