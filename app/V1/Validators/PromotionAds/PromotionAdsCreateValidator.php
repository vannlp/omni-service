<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:59 PM
 */

namespace App\V1\Validators\PromotionAds;

use App\Http\Validators\ValidatorBase;
use App\PromotionAds;
use App\Supports\Message;
use Illuminate\Http\Request;

class PromotionAdsCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'title'                  => 'required',
            'image_id'               => 'exists:files,id,deleted_at,NULL',
            'company_id'             => 'exists:companies,id,deleted_at,NULL',
            'description'            => 'nullable',
            'coupon'                 => 'nullable',
        ];
    }

    protected function attributes()
    {
        return [
            'title' => Message::get("title"),
            'image_id' => Message::get("image_id"),
            'company_id' => Message::get("company_id"),
            'description' => Message::get("description"),
            'coupon' => Message::get("coupon")
        ];
    }
}
