<?php
/**
 * User: dai.ho
 * Date: 15/05/2020
 * Time: 1:29 PM
 */

namespace App;


class ProductInfoImport extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_info_dms_imports';

    protected $fillable = [
        "id",
        "code",
        "product_info_name",
        "description",
        "status",
        "type",        
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
    ];

    
}
