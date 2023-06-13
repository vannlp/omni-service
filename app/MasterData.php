<?php
/**
 * Date: 2/21/2019
 * Time: 1:14 PM
 */

namespace App;

/**
 * Class Master
 * @package App
 */
class MasterData extends BaseModel
{
    /**
     * @var string
     */

    protected $table = 'master_data';
    protected $fillable = [
        "id",
        "code",
        "name",
        "status",
        "type",
        "data",
        "description",
        "sort",
        "store_id",
        "company_id",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
    ];

    public function type()
    {
        return $this->hasOne(__NAMESPACE__ . '\MasterDataType', 'code', 'type');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }
}