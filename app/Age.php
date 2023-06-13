<?php

namespace App;

class Age extends BaseModel
{
    protected $table = 'ages';

    protected $fillable
        = [
            'code',
            'name',
            'company_id',
            'store_id',
            'deleted',
            'updated_by',
            'created_by',
            'updated_at',
            'created_at',
        ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }
}
