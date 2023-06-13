<?php
/**
 * Date: 2/23/2019
 * Time: 1:44 PM
 */

namespace App;

/**
 * Class MasterDataType
 * @package App
 */
class MasterDataType extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'master_data_type';

    protected $fillable = [
        "id",
        "type",
        "name",
        "description",
        "data",
        "status",
        "sort",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
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