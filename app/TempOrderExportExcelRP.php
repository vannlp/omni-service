<?php

namespace App;

class TempOrderExportExcelRP extends BaseModel
{
    protected $table = 'temp_order_export_excel';

    protected $connection = 'mysql2';

    protected $fillable = [
        'order_code',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
