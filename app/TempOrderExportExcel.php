<?php

namespace App;

class TempOrderExportExcel extends BaseModel
{
    protected $table = 'temp_order_export_excel';

    protected $fillable = [
        'order_code',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
