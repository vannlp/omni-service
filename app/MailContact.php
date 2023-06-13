<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class MailContact extends BaseModel
{
    protected $table = 'mail_contacts';

    protected $fillable = [
        'id',
        'name',
        'email',
        'subject',
        'content',
        'company_id',
        'store_id',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}
