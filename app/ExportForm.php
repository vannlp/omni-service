<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class ExportForm extends BaseModel
{
    protected $table = 'export_forms';

    protected $fillable = [
        'id',
        'name',
        'email',
        'country',
        'company',
        'inquiry',
        'advise',
        'please',
        'area_anwser_1',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}
