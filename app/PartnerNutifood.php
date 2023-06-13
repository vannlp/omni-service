<?php

namespace App;

class PartnerNutifood extends BaseModel
{
    protected $table = 'partner_nutifoods';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'id_number',
        'id_images',
        'cooperate',
        'tax',
        'address',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'anwser_1',
        'anwser_2',
        'anwser_3',
        'anwser_4',
        'anwser_5',
        "city_code",
        "ward_code",
        "district_code",
        "city_name",
        "district_name",
        "ward_name",
        "bank_code",
    ];
}
